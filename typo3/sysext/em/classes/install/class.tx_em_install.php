<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Steffen Kamper (info@sk-typo3.de)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Module: Extension manager, (un-)install extensions
 *
 * @author	Steffen Kamper <info@sk-typo3.de>
 */


class tx_em_Install {

	/**
	 * Parent module object
	 *
	 * @var SC_mod_tools_em_index
	 */
	protected $parentObject;

	/**
	 * Instance of EM API
	 *
	 * @var tx_em_API
	 */
	protected $api;

	/**
	 *
	 * @var t3lib_install
	 */
	public $install;

	/**
	 * @var t3lib_install_Sql
	 */
	protected $installerSql = NULL;

	/**
	 * @var integer
	 */
	protected $systemInstall = 0; // If "1" then installs in the sysext directory is allowed. Default: 0

	/**
	 * @var boolean
	 */
	protected $silentMode;

	/**
	 * Constructor
	 *
	 * @param SC_mod_tools_em_index $parentObject
	 */
	public function __construct($parentObject = NULL) {
		$GLOBALS['LANG']->includeLLFile(t3lib_extMgm::extPath('em', 'language/locallang.xml'));
		$this->parentObject = $parentObject;
		$this->api = t3lib_div::makeInstance('tx_em_API');
		$this->install = t3lib_div::makeInstance('t3lib_install');
		$this->installerSql = t3lib_div::makeInstance('t3lib_install_Sql');
		$this->install->INSTALL = t3lib_div::_GP('TYPO3_INSTALL');
		$this->systemInstall = isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['allowSystemInstall']) && $GLOBALS['TYPO3_CONF_VARS']['EXT']['allowSystemInstall'];
	}

	/**
	 * Set silent mode to prevent flashmessages
	 *
	 * @param  boolean $silentMode
	 * @return void
	 */
	public function setSilentMode($silentMode) {
		$this->silentMode = $silentMode ? TRUE : FALSE;
	}

	/**
	 * Imports the data of an extension  from upload
	 *
	 * @param  $uploadedTempFile
	 * @param  $location
	 * @param bool $uploadOverwrite
	 * @return array
	 */
	public function uploadExtensionFile($uploadedTempFile, $location, $uploadOverwrite = FALSE) {
		$error = '';
		$fileContent = t3lib_div::getUrl($uploadedTempFile);
		if (!$fileContent) {
			return array(
				'success' => FALSE,
				'error' => $GLOBALS['LANG']->getLL('ext_import_file_empty')
			);
		}

		// Decode file data:
		$terConnection = t3lib_div::makeInstance('tx_em_Connection_Ter', $this);
		$fetchData = $terConnection->decodeExchangeData($fileContent);
		if (is_array($fetchData)) {
			$extKey = $fetchData[0]['extKey'];
			if ($extKey) {
				if (!$uploadOverwrite) {
					$comingExtPath = tx_em_Tools::typePath($location) . $extKey . '/';
					if (@is_dir($comingExtPath)) {
						$error = sprintf($GLOBALS['LANG']->getLL('ext_import_ext_present_no_overwrite'), $comingExtPath) .
								'<br />' . $GLOBALS['LANG']->getLL('ext_import_ext_present_nothing_done');

					} // ... else go on, install...
				} // ... else go on, install...
			} else {
				$error = $GLOBALS['LANG']->getLL('ext_import_no_key');
			}
		} else {
			$error = sprintf($GLOBALS['LANG']->getLL('ext_import_wrong_file_format'), $fetchData);
		}

		if ($error) {
			return array(FALSE, $error);
		} else {
			return array(TRUE, $fetchData);
		}

	}

	/**
	 * Installs an extension.
	 *
	 * @param  $fetchData
	 * @param  $loc
	 * @param  $version
	 * @param  $uploadedTempFile
	 * @param  $dontDelete
	 * @return mixed|string
	 */
	public function installExtension($fetchData, $loc, $version, $uploadedTempFile, $dontDelete) {
		$xmlHandler =& $this->parentObject->xmlHandler;
		$extensionList =& $this->parentObject->extensionList;
		$extensionDetails =& $this->parentObject->extensionDetails;
		$content = '';

		if (tx_em_Tools::importAsType($loc)) {
			if (is_array($fetchData)) { // There was some data successfully transferred
				if ($fetchData[0]['extKey'] && is_array($fetchData[0]['FILES'])) {
					$extKey = $fetchData[0]['extKey'];
					if (!isset($fetchData[0]['EM_CONF']['constraints'])) {
						$fetchData[0]['EM_CONF']['constraints'] = $xmlHandler->extensionsXML[$extKey]['versions'][$version]['dependencies'];
					}
					$EM_CONF = tx_em_Tools::fixEMCONF($fetchData[0]['EM_CONF']);
					if (!$EM_CONF['lockType'] || !strcmp($EM_CONF['lockType'], $loc)) {
						// check dependencies, act accordingly if ext is loaded
						list($instExtInfo,) = $extensionList->getInstalledExtensions();
						$depStatus = $this->checkDependencies($extKey, $EM_CONF, $instExtInfo);
						if (t3lib_extMgm::isLoaded($extKey) && !$depStatus['returnCode']) {
							$content .= $depStatus['html'];
							if ($uploadedTempFile) {
								$content .= '<input type="hidden" name="CMD[alreadyUploaded]" value="' . $uploadedTempFile . '" />';
							}
						} else {
							$res = $this->clearAndMakeExtensionDir($fetchData[0], $loc, $dontDelete);
							if (is_array($res)) {
								$extDirPath = trim($res[0]);
								if ($extDirPath && @is_dir($extDirPath) && substr($extDirPath, -1) == '/') {

									$emConfFile = $extensionDetails->construct_ext_emconf_file($extKey, $EM_CONF);
									$dirs = tx_em_Tools::extractDirsFromFileList(array_keys($fetchData[0]['FILES']));

									$res = tx_em_Tools::createDirsInPath($dirs, $extDirPath);
									if (!$res) {
										$writeFiles = $fetchData[0]['FILES'];
										$writeFiles['ext_emconf.php']['content'] = $emConfFile;
										$writeFiles['ext_emconf.php']['content_md5'] = md5($emConfFile);

										// Write files:
										foreach ($writeFiles as $theFile => $fileData) {
											t3lib_div::writeFile($extDirPath . $theFile, $fileData['content']);
											if (!@is_file($extDirPath . $theFile)) {
												if (!$this->silentMode) {
													$flashMessage = t3lib_div::makeInstance(
														't3lib_FlashMessage',
														sprintf($GLOBALS['LANG']->getLL('ext_import_file_not_created'),
														$extDirPath . $theFile),
														'',
														t3lib_FlashMessage::ERROR
													);
													$content .= $flashMessage->render();
												} else {
													if (!$this->silentMode) {
														$flashMessage = t3lib_div::makeInstance(
															't3lib_FlashMessage',
															sprintf($GLOBALS['LANG']->getLL('ext_import_file_not_created'), $extDirPath . $theFile),
															'',
															t3lib_FlashMessage::ERROR
														);
														$content .= $flashMessage->render();
													} else {
														$content .= sprintf($GLOBALS['LANG']->getLL('ext_import_file_not_created'),
																$extDirPath . $theFile) . '<br />';
													}
												}
											} elseif (md5(t3lib_div::getUrl($extDirPath . $theFile)) != $fileData['content_md5']) {
												$content .= sprintf($GLOBALS['LANG']->getLL('ext_import_file_corrupted'),
														$extDirPath . $theFile) . '<br />';
											}
										}

										t3lib_div::fixPermissions($extDirPath, TRUE);

										// No content, no errors. Create success output here:
										if (!$content) {
											$messageContent = sprintf($GLOBALS['LANG']->getLL('ext_import_success_folder'), $extDirPath) . '<br />';

											$uploadSucceed = TRUE;

											// Fix TYPO3_MOD_PATH for backend modules in extension:
											$modules = t3lib_div::trimExplode(',', $EM_CONF['module'], 1);
											if (count($modules)) {
												foreach ($modules as $mD) {
													$confFileName = $extDirPath . $mD . '/conf.php';
													if (@is_file($confFileName)) {
														$messageContent .= tx_em_Tools::writeTYPO3_MOD_PATH($confFileName, $loc, $extKey . '/' . $mD . '/') . '<br />';
													} else {
														$messageContent .= sprintf($GLOBALS['LANG']->getLL('ext_import_no_conf_file'),
															$confFileName) . '<br />';
													}
												}
											}
											// NOTICE: I used two hours trying to find out why a script, ext_emconf.php, written twice and in between included by PHP did not update correct the second time. Probably something with PHP-A cache and mtime-stamps.
											// But this order of the code works.... (using the empty Array with type, EMCONF and files hereunder).

											// Writing to ext_emconf.php:
											$sEMD5A = $extensionDetails->serverExtensionMD5array($extKey, array('type' => $loc, 'EM_CONF' => array(), 'files' => array()));
											$EM_CONF['_md5_values_when_last_written'] = serialize($sEMD5A);
											$emConfFile = $extensionDetails->construct_ext_emconf_file($extKey, $EM_CONF);
											t3lib_div::writeFile($extDirPath . 'ext_emconf.php', $emConfFile);

											$messageContent .= 'ext_emconf.php: ' . $extDirPath . 'ext_emconf.php<br />';
											$messageContent .= $GLOBALS['LANG']->getLL('ext_import_ext_type') . ' ';
											$messageContent .= $this->api->typeLabels[$loc] . '<br />';
											$messageContent .= '<br />';

											// Remove cache files:
											$updateContent = '';
											if (t3lib_extMgm::isLoaded($extKey)) {
												if (t3lib_extMgm::removeCacheFiles()) {
													$messageContent .= $GLOBALS['LANG']->getLL('ext_import_cache_files_removed') . '<br />';
												}
													// Flush autoloader cache
												$GLOBALS['typo3CacheManager']->getCache('cache_phpcode')->flushByTag('t3lib_autoloader');

												list($new_list) = $this->parentObject->extensionList->getInstalledExtensions();
												$updateContent = $this->updatesForm($extKey, $new_list[$extKey], 1, t3lib_div::linkThisScript(array(
													'CMD[showExt]' => $extKey,
													'SET[singleDetails]' => 'info'
												)));
											}

											if (!$this->silentMode) {
												$flashMessage = t3lib_div::makeInstance(
													't3lib_FlashMessage',
													$messageContent,
													$GLOBALS['LANG']->getLL('ext_import_success')
												);
												$content = $flashMessage->render() . $updateContent;
											} else {
												$content = $updateContent;
											}


											// Install / Uninstall:
											if (!$this->parentObject->CMD['standAlone']) {
												$content .= '<h3>' . $GLOBALS['LANG']->getLL('ext_import_install_uninstall') . '</h3>';
												$content .= $new_list[$extKey] ?
														'<a href="' . htmlspecialchars(t3lib_div::linkThisScript(array(
															'CMD[showExt]' => $extKey,
															'CMD[remove]' => 1,
															'CMD[clrCmd]' => 1,
															'SET[singleDetails]' => 'info'
														))) . '">' .
																tx_em_Tools::removeButton() . ' ' . $GLOBALS['LANG']->getLL('ext_import_uninstall') . '</a>' :
														'<a href="' . htmlspecialchars(t3lib_div::linkThisScript(array(
															'CMD[showExt]' => $extKey,
															'CMD[load]' => 1,
															'CMD[clrCmd]' => 1,
															'SET[singleDetails]' => 'info'
														))) . '">' .
																tx_em_Tools::installButton() . ' ' . $GLOBALS['LANG']->getLL('ext_import_install') . '</a>';
											} else {
												$content = $GLOBALS['LANG']->getLL('ext_import_imported') .
														'<br /><br />';
												if ($this->silentMode || t3lib_div::_GP('nodoc')) {
													$content .= '<a id="closewindow" href="javascript:parent.TYPO3.EM.Tools.closeImportWindow();">' . $GLOBALS['LANG']->getLL('ext_import_close') . '</a>';
												} else {
													$content .= '<a href="javascript:opener.top.list.iframe.document.forms[0].submit();window.close();">' .
														$GLOBALS['LANG']->getLL('ext_import_close_check') . '</a>';
												}

											}
										}
									} else {
										if (!$this->silentMode) {
											$flashMessage = t3lib_div::makeInstance(
												't3lib_FlashMessage',
												$res,
												'',
												t3lib_FlashMessage::ERROR
											);
											$content = $flashMessage->render();
										} else {
											$content = $res;
										}
									}
								} else {
									if (!$this->silentMode) {
										$flashMessage = t3lib_div::makeInstance(
											't3lib_FlashMessage',
											sprintf($GLOBALS['LANG']->getLL('ext_import_ext_path_different'), $extDirPath),
											'',
											t3lib_FlashMessage::ERROR
										);
										$content = $flashMessage->render();
									} else {
										$content = sprintf($GLOBALS['LANG']->getLL('ext_import_ext_path_different'), $extDirPath);
									}
								}
							} else {
								if (!$this->silentMode) {
									$flashMessage = t3lib_div::makeInstance(
										't3lib_FlashMessage',
										$res,
										'',
										t3lib_FlashMessage::ERROR
									);
									$content = $flashMessage->render();
								} else {
									$content = $res;
								}
							}
						}
					} else {
						if (!$this->silentMode) {
							$flashMessage = t3lib_div::makeInstance(
								't3lib_FlashMessage',
								sprintf($GLOBALS['LANG']->getLL('ext_import_ext_only_here'),
									$this->typePaths[$EM_CONF['lockType']], $EM_CONF['lockType']),
								'',
								t3lib_FlashMessage::ERROR
							);
							$content = $flashMessage->render();
						} else {
							$content = sprintf($GLOBALS['LANG']->getLL('ext_import_ext_only_here'),
								tx_em_Tools::typePath($EM_CONF['lockType']), $EM_CONF['lockType']);
						}
					}
				} else {
					if (!$this->silentMode) {
						$flashMessage = t3lib_div::makeInstance(
							't3lib_FlashMessage',
							$GLOBALS['LANG']->getLL('ext_import_no_ext_key_files'),
							'',
							t3lib_FlashMessage::ERROR
						);
						$content = $flashMessage->render();
					} else {
						$content = $GLOBALS['LANG']->getLL('ext_import_no_ext_key_files');
					}
				}
			} else {
				if (!$this->silentMode) {
					$flashMessage = t3lib_div::makeInstance(
						't3lib_FlashMessage',
						sprintf($GLOBALS['LANG']->getLL('ext_import_data_transfer'), $fetchData),
						'',
						t3lib_FlashMessage::ERROR
					);
					$content = $flashMessage->render();
				} else {
					$content = sprintf($GLOBALS['LANG']->getLL('ext_import_data_transfer'), $fetchData);
				}
			}
		} else {
			if (!$this->silentMode) {
				$flashMessage = t3lib_div::makeInstance(
					't3lib_FlashMessage',
					sprintf($GLOBALS['LANG']->getLL('ext_import_no_install_here'), $this->typePaths[$loc]),
					'',
					t3lib_FlashMessage::ERROR
				);
				$content = $flashMessage->render();
			} else {
				$content = sprintf($GLOBALS['LANG']->getLL('ext_import_no_install_here'), tx_em_Tools::typePath($loc));
			}
		}

		return $content;
	}

	/**
	 *Check extension dependencies
	 *
	 * @param	string		$extKey
	 * @param	array		$conf
	 * @param	array		$instExtInfo
	 * @return	array
	 */
	function checkDependencies($extKey, $conf, $instExtInfo) {
		$content = '';
		$depError = FALSE;
		$depIgnore = FALSE;
		$msg = array();
		$depsolver = t3lib_div::_POST('depsolver');

		if (isset($conf['constraints']['depends']) && is_array($conf['constraints']['depends'])) {
			foreach ($conf['constraints']['depends'] as $depK => $depV) {
				if ($depsolver['ignore'][$depK]) {
					$msg[] = '<br />' . sprintf($GLOBALS['LANG']->getLL('checkDependencies_ignored'),
						$depK) . '
						<input type="hidden" value="1" name="depsolver[ignore][' . $depK . ']" />';
					$depIgnore = TRUE;
					continue;
				}
				if ($depK == 'php') {
					if (!$depV) {
						continue;
					}
					$versionRange = tx_em_Tools::splitVersionRange($depV);
					$phpv = strstr(PHP_VERSION, '-') ? substr(PHP_VERSION, 0, strpos(PHP_VERSION, '-')) : PHP_VERSION; // Linux distributors like to add suffixes, like in 5.1.2-1. Those must be ignored!
					if ($versionRange[0] != '0.0.0' && version_compare($phpv, $versionRange[0], '<')) {
						$msg[] = '<br />' . sprintf($GLOBALS['LANG']->getLL('checkDependencies_php_too_low'),
							$phpv, $versionRange[0]);
						$msg[] = '&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" value="1" name="depsolver[ignore][' . $depK . ']" id="checkIgnore_' . $depK . '" />
							<label for="checkIgnore_' . $depK . '">' . $GLOBALS['LANG']->getLL('checkDependencies_ignore_requirement') . '</label>';
						$depError = TRUE;
						continue;
					} elseif ($versionRange[1] != '0.0.0' && version_compare($phpv, $versionRange[1], '>')) {
						$msg[] = '<br />' . sprintf($GLOBALS['LANG']->getLL('checkDependencies_php_too_high'),
							$phpv, $versionRange[1]);
						$msg[] = '&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" value="1" name="depsolver[ignore][' . $depK . ']" id="checkIgnore_' . $depK . '" />
							<label for="checkIgnore_' . $depK . '">' . $GLOBALS['LANG']->getLL('checkDependencies_ignore_requirement') . '</label>';
						$depError = TRUE;
						continue;
					}

				} elseif ($depK == 'typo3') {
					if (!$depV) {
						continue;
					}

					// if the current TYPO3 version is a development version (like TYPO3 4.4-dev),
					// then it should behave like TYPO3 4.4.0
					$t3version = TYPO3_version;
					if (stripos($t3version, '-dev')
							|| stripos($t3version, '-alpha')
							|| stripos($t3version, '-beta')
							|| stripos($t3version, '-RC')) {
						// find the last occurence of "-" and replace that part with a ".0"
						$t3version = substr($t3version, 0, strrpos($t3version, '-')) . '.0';
					}

					$versionRange = tx_em_Tools::splitVersionRange($depV);
					if ($versionRange[0] != '0.0.0' && version_compare($t3version, $versionRange[0], '<')) {
						$msg[] = '<br />' . sprintf($GLOBALS['LANG']->getLL('checkDependencies_typo3_too_low'),
							$t3version, $versionRange[0]);
						$msg[] = '&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" value="1" name="depsolver[ignore][' . $depK . ']" id="checkIgnore_' . $depK . '" />
							<label for="checkIgnore_' . $depK . '">' . $GLOBALS['LANG']->getLL('checkDependencies_ignore_requirement') . '</label>';
						$depError = TRUE;
						continue;
					} elseif ($versionRange[1] != '0.0.0' && version_compare($t3version, $versionRange[1], '>')) {
						$msg[] = '<br />' . sprintf($GLOBALS['LANG']->getLL('checkDependencies_typo3_too_high'),
							$t3version, $versionRange[1]);
						$msg[] = '&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" value="1" name="depsolver[ignore][' . $depK . ']" id="checkIgnore_' . $depK . '" />
							<label for="checkIgnore_' . $depK . '">' . $GLOBALS['LANG']->getLL('checkDependencies_ignore_requirement') . '</label>';
						$depError = TRUE;
						continue;
					}
				} elseif (strlen($depK) && !t3lib_extMgm::isLoaded($depK)) { // strlen check for braindead empty dependencies coming from extensions...
					if (!isset($instExtInfo[$depK])) {
						$msg[] = '<br />' . sprintf($GLOBALS['LANG']->getLL('checkDependencies_ext_not_available'),
							$depK);
						$msg[] = '&nbsp;&nbsp;&nbsp;&nbsp;' . t3lib_iconWorks::getSpriteIcon('actions-system-extension-import', array('title' => $GLOBALS['LANG']->getLL('checkDependencies_import_ext'))) . '&nbsp;
							<a href="#" onclick="window.open(\'' . t3lib_div::linkThisUrl($this->parentObject->script, array(
							'CMD[importExt]' => $depK,
							'CMD[loc]' => 'L',
							'CMD[standAlone]' => 1
						)) . '\', \'' . md5($instExtInfo[$depK]['EM_CONF']['title']) . '\', \'width=650,height=500,scrollbars=yes\'); return false;" target="_blank">' . $GLOBALS['LANG']->getLL('checkDependencies_import_now') . '</a>';
						$msg[] = '&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" value="1" name="depsolver[ignore][' . $depK . ']" id="checkIgnore_' . $depK . '" />
							<label for="checkIgnore_' . $depK . '">' . $GLOBALS['LANG']->getLL('checkDependencies_ignore_ext_requirement') . '</label>';
					} else {
						$msg[] = '<br />' . sprintf($GLOBALS['LANG']->getLL('checkDependencies_ext_not_installed'),
							$depK, $instExtInfo[$depK]['EM_CONF']['title']);
						$msg[] = '&nbsp;&nbsp;&nbsp;&nbsp;' . tx_em_Tools::installButton() . '&nbsp;
							<a href="#" onclick="window.open(\'' . t3lib_div::linkThisUrl($this->parentObject->script, array(
							'CMD[showExt]' => $depK,
							'CMD[load]' => 1,
							'CMD[clrCmd]' => 1,
							'CMD[standAlone]' => 1,
							'SET[singleDetails]' => 'info'
						)) .
								'\', \'' . md5($instExtInfo[$depK]['EM_CONF']['title']) . '\', \'width=650,height=500,scrollbars=yes\'); return false; " target="_blank">' . $GLOBALS['LANG']->getLL('checkDependencies_install_now') . '</a>';
						$msg[] = '&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" value="1" name="depsolver[ignore][' . $depK . ']" id="checkIgnore_' . $depK . '" />
							<label for="checkIgnore_' . $depK . '">' . $GLOBALS['LANG']->getLL('checkDependencies_ignore_ext_requirement') . '</label>';
					}
					$depError = TRUE;
				} else {
					$versionRange = tx_em_Tools::splitVersionRange($depV);
					if ($versionRange[0] != '0.0.0' && version_compare($instExtInfo[$depK]['EM_CONF']['version'], $versionRange[0], '<')) {
						$msg[] = '<br />' . sprintf($GLOBALS['LANG']->getLL('checkDependencies_ext_too_low'),
							$depK, $instExtInfo[$depK]['EM_CONF']['version'], $versionRange[0]);
						$msg[] = '&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" value="1" name="depsolver[ignore][' . $depK . ']" id="checkIgnore_' . $depK . '" />
							<label for="checkIgnore_' . $depK . '">' . $GLOBALS['LANG']->getLL('checkDependencies_ignore_requirement') . '</label>';
						$depError = TRUE;
						continue;
					} elseif ($versionRange[1] != '0.0.0' && version_compare($instExtInfo[$depK]['EM_CONF']['version'], $versionRange[1], '>')) {
						$msg[] = '<br />' . sprintf($GLOBALS['LANG']->getLL('checkDependencies_ext_too_high'),
							$depK, $instExtInfo[$depK]['EM_CONF']['version'], $versionRange[1]);
						$msg[] = '&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" value="1" name="depsolver[ignore][' . $depK . ']" id="checkIgnore_' . $depK . '" />
							<label for="checkIgnore_' . $depK . '">' . $GLOBALS['LANG']->getLL('checkDependencies_ignore_requirement') . '</label>';
						$depError = TRUE;
						continue;
					}
				}
			}
		}

		if ($depError || $depIgnore) {
				// dependent of the context in which we are (AJAX with tx_em_connection_ExtDirectServer or
				// old-school rendering with historic pages in shiny ExtJS frames) we have various options
			if ($this->parentObject instanceof SC_mod_tools_em_index) {
					// we're in the lucky position to ask the user to uninstall the extension again
				$content .= $this->parentObject->doc->section(
					$GLOBALS['LANG']->getLL('removeExtFromList_dependency_error'),
					implode('<br />', $msg), 0, 1, 2
				);
			} elseif ($this->parentObject instanceof tx_em_Connection_ExtDirectServer) {
					// with ExtDirect, we are in a context, where we cannot ask the user for feedback
					// thus we silently uninstall the newly uploaded extension
				$this->parentObject->disableExtension($extKey);
			}
		}

		// Check conflicts with other extensions:
		$conflictError = FALSE;
		$conflictIgnore = FALSE;
		$msg = array();

		if (isset($conf['constraints']['conflicts']) && is_array($conf['constraints']['conflicts'])) {
			foreach ((array) $conf['constraints']['conflicts'] as $conflictK => $conflictV) {
				if ($depsolver['ignore'][$conflictK]) {
					$msg[] = '<br />' . sprintf($GLOBALS['LANG']->getLL('checkDependencies_conflict_ignored'),
						$conflictK) . '
						<input type="hidden" value="1" name="depsolver[ignore][' . $conflictK . ']" />';
					$conflictIgnore = TRUE;
					continue;
				}
				if (t3lib_extMgm::isLoaded($conflictK)) {
					$versionRange = tx_em_Tools::splitVersionRange($conflictV);
					if ($versionRange[0] != '0.0.0' && version_compare($instExtInfo[$conflictK]['EM_CONF']['version'], $versionRange[0], '<')) {
						continue;
					}
					elseif ($versionRange[1] != '0.0.0' && version_compare($instExtInfo[$conflictK]['EM_CONF']['version'], $versionRange[1], '>')) {
						continue;
					}
					$msg[] = sprintf($GLOBALS['LANG']->getLL('checkDependencies_conflict_remove'),
						$extKey, $conflictK, $instExtInfo[$conflictK]['EM_CONF']['title'], $conflictK, $extKey);
					$msg[] = '&nbsp;&nbsp;&nbsp;&nbsp;' . tx_em_Tools::removeButton() . '&nbsp;
						<a href="' . htmlspecialchars(t3lib_div::linkThisScript(array(
						'CMD[showExt]' => $conflictK,
						'CMD[remove]' => 1,
						'CMD[clrCmd]' => 1,
						'CMD[standAlone]' => 1,
						'SET[singleDetails]' => 'info'
					))) .
							'" target="_blank">' . $GLOBALS['LANG']->getLL('checkDependencies_remove_now') . '</a>';
					$msg[] = '&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" value="1" name="depsolver[ignore][' . $conflictK . ']" id="checkIgnore_' . $conflictK . '" />
						<label for="checkIgnore_' . $conflictK . '">' . $GLOBALS['LANG']->getLL('checkDependencies_ignore_conflict') . '</label>';
					$conflictError = TRUE;
				}
			}
		}

		if ($conflictError || $conflictIgnore) {
				// dependent of the context in which we are (AJAX with tx_em_connection_ExtDirectServer or
				// old-school rendering with historic pages in shiny ExtJS frames) we have various options
			if ($this->parentObject instanceof SC_mod_tools_em_index) {
					// we're in the lucky position to ask the user to uninstall the extension again
				$content .= $this->parentObject->doc->section(
					$GLOBALS['LANG']->getLL('checkDependencies_conflict_error'), implode('<br />', $msg), 0, 1, 2
				);
			} elseif ($this->parentObject instanceof tx_em_Connection_ExtDirectServer) {
					// with ExtDirect, we are in a context, where we cannot ask the user for feedback
					// thus we silently uninstall the newly uploaded extension
				$this->parentObject->disableExtension($extKey);
			}
		}

		// Check suggests on other extensions:
		if (isset($conf['constraints']['suggests']) && is_array($conf['constraints']['suggests'])) {
			$suggestion = FALSE;
			$suggestionIgnore = FALSE;
			$msg = array();
			foreach ($conf['constraints']['suggests'] as $suggestK => $suggestV) {
				if ($depsolver['ignore'][$suggestK]) {
					$msg[] = '<br />' . sprintf($GLOBALS['LANG']->getLL('checkDependencies_suggestion_ignored'),
						$suggestK) . '
				<input type="hidden" value="1" name="depsolver[ignore][' . $suggestK . ']" />';
					$suggestionIgnore = TRUE;
					continue;
				}
				if (!t3lib_extMgm::isLoaded($suggestK)) {
					if (!isset($instExtInfo[$suggestK])) {
						$msg[] = sprintf($GLOBALS['LANG']->getLL('checkDependencies_suggest_import'),
							$suggestK);
						$msg[] = '&nbsp;&nbsp;&nbsp;&nbsp;' . t3lib_iconWorks::getSpriteIcon('actions-system-extension-import', array('title' => $GLOBALS['LANG']->getLL('checkDependencies_import_ext'))) . '&nbsp;
							<a href="#" onclick="window.open(\'' . t3lib_div::linkThisScript(array(
							'CMD[importExt]' => $suggestK,
							'CMD[loc]' => 'L',
							'CMD[standAlone]' => 1
						)) . '\', \'' . md5($suggestK) . '\', \'width=650,height=500,scrollbars=yes\'); return false; " target="_blank">' . $GLOBALS['LANG']->getLL('checkDependencies_import_now') . '</a>';
						$msg[] = '&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" value="1" name="depsolver[ignore][' . $suggestK . ']" id="checkIgnore_' . $suggestK . '" />
							<label for="checkIgnore_' . $suggestK . '">' . $GLOBALS['LANG']->getLL('checkDependencies_ignore_suggestion') . '</label>';
					} else {
						$msg[] = sprintf($GLOBALS['LANG']->getLL('checkDependencies_suggest_installation'),
							$suggestK, $instExtInfo[$suggestK]['EM_CONF']['title']);
						$msg[] = '&nbsp;&nbsp;&nbsp;&nbsp;' . tx_em_Tools::installButton() . '&nbsp;
							<a href="#" onclick="window.open(\''  . htmlspecialchars(t3lib_div::linkThisScript(array(
							'CMD[showExt]' => $suggestK,
							'CMD[load]' => 1,
							'CMD[clrCmd]' => 1,
							'CMD[standAlone]' => 1,
							'SET[singleDetails]' => 'info'
						))) .
								'\', \'' . md5($suggestK) . '\', \'width=650,height=500,scrollbars=yes\'); return false; " target="_blank">' . $GLOBALS['LANG']->getLL('checkDependencies_install_now') . '</a>';
						$msg[] = '&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" value="1" name="depsolver[ignore][' . $suggestK . ']" id="checkIgnore_' . $suggestK . '" />
							<label for="checkIgnore_' . $suggestK . '">' . $GLOBALS['LANG']->getLL('checkDependencies_ignore_suggestion') . '</label>';
					}
					$suggestion = TRUE;
				}
			}

			if ($suggestion || $suggestionIgnore) {
					// dependent of the context in which we are (AJAX with tx_em_connection_ExtDirectServer or
					// old-school rendering with historic pages in shiny ExtJS frames) we have various options
				if ($this->parentObject instanceof SC_mod_tools_em_index) {
						// we're in the lucky position to ask the user to uninstall the extension again
					$content .= $this->parentObject->doc->section(
						sprintf($GLOBALS['LANG']->getLL('checkDependencies_exts_suggested_by_ext'), $extKey),
						implode('<br />', $msg), 0, 1, 1
					);
				} elseif ($this->parentObject instanceof tx_em_Connection_ExtDirectServer) {
						// with ExtDirect, we are in a context, where we cannot ask the user for feedback
						// thus we silently uninstall the newly uploaded extension
					$this->parentObject->disableExtension($extKey);
				}
			}
		}

			// only when we are in old-school HTML output mode (thus not ExtDirect), we can ask for user feedback
		if (($depError || $conflictError || $suggestion) && ($this->parentObject instanceof SC_mod_tools_em_index)) {
			foreach ($this->parentObject->CMD as $k => $v) {
				$content .= '<input type="hidden" name="CMD[' . $k . ']" value="' . $v . '" />';
			}
			$content .= '<br /><br /><input type="submit" value="' . $GLOBALS['LANG']->getLL('checkDependencies_try_again') . '" />';

			if (t3lib_div::_GP('nodoc')) {
				$content .= '<input type="hidden" name="nodoc" value="1" />';
			}

			return array(
				'returnCode' => FALSE,
				'html' => '<form action="' . $this->parentObject->script . '" method="post" name="depform">' . $content . '</form>');
		}

		return array(
			'returnCode' => TRUE
		);
	}


	/**
	 * Delete extension from the file system
	 *
	 * @param	string		Extension key
	 * @param	array		Extension info array
	 * @return	string		Returns message string about the status of the operation
	 */
	function extDelete($extKey, $extInfo, $command) {
		$content = '';
		$absPath = tx_em_Tools::getExtPath($extKey, $extInfo['type']);
		if (t3lib_extMgm::isLoaded($extKey)) {
			return $GLOBALS['LANG']->getLL('extDelete_ext_active');
		} elseif (!tx_em_Tools::deleteAsType($extInfo['type'])) {
			return sprintf($GLOBALS['LANG']->getLL('extDelete_wrong_scope'),
				$this->api->typeLabels[$extInfo['type']]
			);
		} elseif (t3lib_div::inList('G,L', $extInfo['type'])) {
			if ($command['doDelete'] && !strcmp($absPath, urldecode($command['absPath']))) {
				$res = $this->removeExtDirectory($absPath);
				if ($res) {
					if (!$this->silentMode) {
						$flashMessage = t3lib_div::makeInstance(
							't3lib_FlashMessage',
							nl2br($res),
							sprintf($GLOBALS['LANG']->getLL('extDelete_remove_dir_failed'), $absPath),
							t3lib_FlashMessage::ERROR
						);
						return $flashMessage->render();
					}
					return '';
				} else {
					if (!$this->silentMode) {
						$flashMessage = t3lib_div::makeInstance(
							't3lib_FlashMessage',
							sprintf($GLOBALS['LANG']->getLL('extDelete_removed'), $absPath),
							$GLOBALS['LANG']->getLL('extDelete_removed_header'),
							t3lib_FlashMessage::OK
						);
						return $flashMessage->render();
					}
					return '';
				}
			} else {
				$areYouSure = $GLOBALS['LANG']->getLL('extDelete_sure');
				$deleteFromServer = $GLOBALS['LANG']->getLL('extDelete_from_server');
				$onClick = "if (confirm('$areYouSure')) {window.location.href='" . t3lib_div::linkThisScript(array(
					'CMD[showExt]' => $extKey,
					'CMD[doDelete]' => 1,
					'CMD[absPath]' => rawurlencode($absPath)
				)) . "';}";
				$content .= '<a class="t3-link deleteLink" href="#" onclick="' . htmlspecialchars($onClick) .
						' return false;"><strong>' . $deleteFromServer . '</strong> ' .
						sprintf($GLOBALS['LANG']->getLL('extDelete_from_location'),
							$this->api->typeLabels[$extInfo['type']],
							substr($absPath, strlen(PATH_site))
						) . '</a>';
				$content .= '<br /><br />' . $GLOBALS['LANG']->getLL('extDelete_backup');
				return $content;
			}
		} else {
			return $GLOBALS['LANG']->getLL('extDelete_neither_global_nor_local');
		}
	}

	/**
	 * Removes the extension directory (including content)
	 *
	 * @param	string		Extension directory to remove (with trailing slash)
	 * @param	boolean		If set, will leave the extension directory
	 * @return	boolean		False on success, otherwise error string.
	 */
	function removeExtDirectory($removePath, $removeContentOnly = 0) {
		$errors = array();
		if (@is_dir($removePath) && substr($removePath, -1) == '/' && (
				t3lib_div::isFirstPartOfStr($removePath, tx_em_Tools::typePath('G')) ||
						t3lib_div::isFirstPartOfStr($removePath, tx_em_Tools::typePath('L')) ||
						(t3lib_div::isFirstPartOfStr($removePath, tx_em_Tools::typePath('S')) && $this->systemInstall) ||
						t3lib_div::isFirstPartOfStr($removePath, PATH_site . $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'] . '_temp_/')) // Playing-around directory...
		) {

			// All files in extension directory:
			$fileArr = t3lib_div::getAllFilesAndFoldersInPath(array(), $removePath, '', 1);
			if (is_array($fileArr)) {

				// Remove files in dirs:
				foreach ($fileArr as $removeFile) {
					if (!@is_dir($removeFile)) {
						if (@is_file($removeFile) && t3lib_div::isFirstPartOfStr($removeFile, $removePath) && strcmp($removeFile, $removePath)) { // ... we are very paranoid, so we check what cannot go wrong: that the file is in fact within the prefix path!
							@unlink($removeFile);
							clearstatcache();
							if (@is_file($removeFile)) {
								$errors[] = sprintf($GLOBALS['LANG']->getLL('rmExtDir_could_not_be_deleted'),
									$removeFile
								);
							}
						} else {
							$errors[] = sprintf($GLOBALS['LANG']->getLL('rmExtDir_error_file'),
								$removeFile, $removePath
							);
						}
					}
				}

				// Remove directories:
				$remDirs = tx_em_Tools::extractDirsFromFileList(t3lib_div::removePrefixPathFromList($fileArr, $removePath));
				$remDirs = array_reverse($remDirs); // Must delete outer directories first...
				foreach ($remDirs as $removeRelDir) {
					$removeDir = $removePath . $removeRelDir;
					if (@is_dir($removeDir)) {
						@rmdir($removeDir);
						clearstatcache();
						if (@is_dir($removeDir)) {
							$errors[] = sprintf($GLOBALS['LANG']->getLL('rmExtDir_error_files_left'),
								$removeDir
							);
						}
					} else {
						$errors[] = sprintf($GLOBALS['LANG']->getLL('rmExtDir_error_no_dir'),
							$removeDir
						);
					}
				}

				// If extension dir should also be removed:
				if (!$removeContentOnly) {
					@rmdir($removePath);
					clearstatcache();
					if (@is_dir($removePath)) {
						$errors[] = sprintf($GLOBALS['LANG']->getLL('rmExtDir_error_folders_left'),
							$removePath
						);
					}
				}
			} else {
				$errors[] = $GLOBALS['LANG']->getLL('rmExtDir_error') . ' ' . $fileArr;
			}
		} else {
			$errors[] = $GLOBALS['LANG']->getLL('rmExtDir_error_unallowed_path') . ' ' . $removePath;
		}

		// Return errors if any:
		return implode(LF, $errors);
	}

	/**
	 * Validates the database according to extension requirements
	 * Prints form for changes if any. If none, returns blank. If an update is ordered, empty is returned as well.
	 * DBAL compliant (based on Install Tool code)
	 *
	 * @param string $extKey Extension key
	 * @param array $extInfo Extension information array
	 * @param boolean $infoOnly If TRUE, returns array with info
	 * @param boolean $showCachingTables If FALSE, don't take the caching tables into account
	 * @return mixed If $infoOnly, returns array with information. Otherwise performs update.
	 */
	function checkDBupdates($extKey, $extInfo, $infoOnly = FALSE, $showCachingTables = TRUE) {

		$dbStatus = array();
		$content = '';
		$hookObjects = array();

		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/mod/tools/em/index.php']['checkDBupdates'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/mod/tools/em/index.php']['checkDBupdates'] as $classData) {
				/** @var $hookObject tx_em_Index_CheckDatabaseUpdatesHook **/
				$hookObject = t3lib_div::getUserObj($classData);
				if (!($hookObject instanceof tx_em_Index_CheckDatabaseUpdatesHook)) {
					throw new UnexpectedValueException('$hookObject must implement interface em_index_checkDatabaseUpdatesHook', 1288418476);
				}
				$hookObjects[] = $hookObject;
			}
		}

		// Updating tables and fields?
		$showUpdateStatements = TRUE;
		if (is_array($extInfo['files']) && in_array('ext_tables.sql', $extInfo['files'])) {
			$path = tx_em_Tools::getExtPath($extKey, $extInfo['type']);
			$fileContent = t3lib_div::getUrl($path . 'ext_tables.sql');
				// Take caching tables into account only if necessary
				// (this is not always the case, because this method is also called, for example,
				// to list all tables for which to dump data for in the extension maintenance operations)
			if ($showCachingTables) {
				$fileContent .= t3lib_cache::getDatabaseTableDefinitions();
			}
			foreach ($hookObjects as $hookObject) {
				/** @var $hookObject tx_em_Index_CheckDatabaseUpdatesHook **/
				$appendableTableDefinitions = $hookObject->appendTableDefinitions($extKey, $extInfo, $fileContent, $this->install, $this->installerSql, $this);
				if ($appendableTableDefinitions) {
					$fileContent .= $appendableTableDefinitions;
					break;
				}
			}

			$FDfile = $this->installerSql->getFieldDefinitions_fileContent($fileContent);
			if (count($FDfile)) {
				$FDdb = $this->installerSql->getFieldDefinitions_database(TYPO3_db);
				$diff = $this->installerSql->getDatabaseExtra($FDfile, $FDdb);
				$update_statements = $this->installerSql->getUpdateSuggestions($diff);

				$dbStatus['structure']['tables_fields'] = $FDfile;
				$dbStatus['structure']['diff'] = $diff;

				// Updating database...
				if (!$infoOnly && is_array($this->install->INSTALL['database_update'])) {
					$this->installerSql->performUpdateQueries($update_statements['add'], $this->install->INSTALL['database_update']);
					$this->installerSql->performUpdateQueries($update_statements['change'], $this->install->INSTALL['database_update']);
					$this->installerSql->performUpdateQueries($update_statements['create_table'], $this->install->INSTALL['database_update']);
				} else {
					foreach ($hookObjects as $hookObject) {
						/** @var $hookObject tx_em_Index_CheckDatabaseUpdatesHook **/
							// Hook that allows pre-processing of database structure modifications.
							// The hook implementation may return a user form that will temporarily
							// replace the standard database update form. This allows additional
							// operations to be performed before the database structure gets updated.
						$preprocessContent = $hookObject->preProcessDatabaseUpdates($extKey, $extInfo, $diff, $this->install, $this);
						if ($preprocessContent) {
							$content .= $preprocessContent;
							$showUpdateStatements = FALSE;
							break;
						}
					}
					if ($showUpdateStatements) {
						$content .= $this->install->generateUpdateDatabaseForm_checkboxes(
							$update_statements['add'], $GLOBALS['LANG']->getLL('checkDBupdates_add_fields'));
						$content .= $this->install->generateUpdateDatabaseForm_checkboxes(
							$update_statements['change'], $GLOBALS['LANG']->getLL('checkDBupdates_changing_fields'), 1, 0, $update_statements['change_currentValue']);
						$content .= $this->install->generateUpdateDatabaseForm_checkboxes(
							$update_statements['create_table'], $GLOBALS['LANG']->getLL('checkDBupdates_add_tables'));
					}
				}
			}
		}

		// Importing static tables?
		if (is_array($extInfo['files']) && in_array('ext_tables_static+adt.sql', $extInfo['files'])) {
			$fileContent = t3lib_div::getUrl(tx_em_Tools::getExtPath($extKey, $extInfo['type']) . 'ext_tables_static+adt.sql');

			$statements = $this->installerSql->getStatementarray($fileContent, 1);
			list($statements_table, $insertCount) = $this->installerSql->getCreateTables($statements, 1);

			// Execute import of static table content:
			if (!$infoOnly && is_array($this->install->INSTALL['database_import'])) {

				// Traverse the tables
				foreach ($this->install->INSTALL['database_import'] as $table => $md5str) {
					if ($md5str == md5($statements_table[$table])) {
						$GLOBALS['TYPO3_DB']->admin_query('DROP TABLE IF EXISTS ' . $table);
						$GLOBALS['TYPO3_DB']->admin_query($statements_table[$table]);

						if ($insertCount[$table]) {
							$statements_insert = $this->installerSql->getTableInsertStatements($statements, $table);

							foreach ($statements_insert as $v) {
								$GLOBALS['TYPO3_DB']->admin_query($v);
							}
						}
					}
				}
			} else {
				$whichTables = $this->installerSql->getListOfTables();
				if (count($statements_table)) {
					$out = '';
					foreach ($statements_table as $table => $definition) {
						$exist = isset($whichTables[$table]);

						$dbStatus['static'][$table]['exists'] = $exist;
						$dbStatus['static'][$table]['count'] = $insertCount[$table];

						$out .= '<tr>
							<td><input type="checkbox" id="check_table_' . $table . '" name="TYPO3_INSTALL[database_import][' . $table . ']" checked="checked" value="' . md5($definition) . '" /></td>
							<td><strong><label for="check_table_' . $table . '">' . $table . '</label></strong></td>
							<td><img src="clear.gif" width="10" height="1" alt="" /></td>
							<td nowrap="nowrap">' .
								($insertCount[$table] ?
										$GLOBALS['LANG']->getLL('checkDBupdates_rows') . ' ' . $insertCount[$table]
										: '') .
								'</td>
							<td><img src="clear.gif" width="10" height="1" alt="" /></td>
							<td nowrap="nowrap">' .
								($exist ?
										t3lib_iconWorks::getSpriteIcon('status-dialog-warning') .
												$GLOBALS['LANG']->getLL('checkDBupdates_table_exists')
										: '') .
								'</td>
							</tr>';
					}
					$content .= '
						<br />
						<h3>' . $GLOBALS['LANG']->getLL('checkDBupdates_import_static_data') . '</h3>
						<table border="0" cellpadding="0" cellspacing="0">' . $out . '</table>
						';
				}
			}
		}

		// Return array of information if $infoOnly, otherwise content.
		return $infoOnly ? $dbStatus : $content;
	}

	/**
	 * Removes the current extension of $type and creates the base folder for the new one (which is going to be imported)
	 *
	 * @param	array		Data for imported extension
	 * @param	string		Extension installation scope (L,G,S)
	 * @param	boolean		If set, nothing will be deleted (neither directory nor files)
	 * @return	mixed		Returns array on success (with extension directory), otherwise an error string.
	 */
	function clearAndMakeExtensionDir($importedData, $type, $dontDelete = 0) {
		if (!$importedData['extKey']) {
			return $GLOBALS['LANG']->getLL('clearMakeExtDir_no_ext_key');
		}

		// Setting install path (L, G, S or fileadmin/_temp_/)
		$path = '';
		switch ((string) $type) {
			case 'G':
			case 'L':
				$path = tx_em_Tools::typePath($type);
				$suffix = '';

				// Creates the typo3conf/ext/ directory if it does NOT already exist:
				if ((string) $type == 'L' && !@is_dir($path)) {
					t3lib_div::mkdir($path);
				}
				break;
			default:
				if ($this->systemInstall && (string) $type == 'S') {
					$path = tx_em_Tools::typePath($type);
					$suffix = '';
				} else {
					$path = PATH_site . $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'] . '_temp_/';
					$suffix = '_' . date('dmy-His');
				}
				break;
		}

		// If the install path is OK...
		if ($path && @is_dir($path)) {

			// Set extension directory:
			$extDirPath = $path . $importedData['extKey'] . $suffix . '/';

			// Install dir was found, remove it then:
			if (@is_dir($extDirPath)) {
				if ($dontDelete) {
					return array($extDirPath);
				}
				$res = $this->removeExtDirectory($extDirPath);
				if ($res) {
					if (!$this->silentMode) {
						$flashMessage = t3lib_div::makeInstance(
							't3lib_FlashMessage',
							nl2br($res),
							sprintf($GLOBALS['LANG']->getLL('clearMakeExtDir_could_not_remove_dir'), $extDirPath),
							t3lib_FlashMessage::ERROR
						);
						return $flashMessage->render();
					}
					return '';
				}
			}

			// We go create...
			t3lib_div::mkdir($extDirPath);
			if (!is_dir($extDirPath)) {
				return sprintf($GLOBALS['LANG']->getLL('clearMakeExtDir_could_not_create_dir'),
					$extDirPath);
			}
			return array($extDirPath);
		} else {
			return sprintf($GLOBALS['LANG']->getLL('clearMakeExtDir_no_dir'),
				$path);
		}
	}


	/*******************************
	 *
	 * Extension analyzing (detailed information)
	 *
	 ******************************/

	/**
	 * Perform a detailed, technical analysis of the available extension on server!
	 * Includes all kinds of verifications
	 * Takes some time to process, therefore use with care, in particular in listings.
	 *
	 * @param string $extKey Extension key
	 * @param array $extInfo Extension information
	 * @param boolean $validity If set, checks for validity of classes etc.
	 * @param boolean $showCachingTables If FALSE, don't take the caching tables into account
	 * @return array Information in an array
	 */
	function makeDetailedExtensionAnalysis($extKey, $extInfo, $validity = FALSE, $showCachingTables = TRUE) {

		// Get absolute path of the extension
		$absPath = tx_em_Tools::getExtPath($extKey, $extInfo['type']);
		$extensionDetails = t3lib_div::makeInstance('tx_em_Extensions_Details', $this);
		$infoArray = array();

		$table_class_prefix = substr($extKey, 0, 5) == 'user_' ? 'user_' : 'tx_' . str_replace('_', '', $extKey) . '_';
		$module_prefix = substr($extKey, 0, 5) == 'user_' ? 'u' : 'tx' . str_replace('_', '', $extKey);

			// Database status:
		$dbInfo = $this->checkDBupdates($extKey, $extInfo, TRUE, $showCachingTables);

		// Database structure required:
		if (is_array($dbInfo['structure']['tables_fields'])) {
			$modify_tables = t3lib_div::trimExplode(',', $extInfo['EM_CONF']['modify_tables'], 1);
			$infoArray['dump_tf'] = array();

			foreach ($dbInfo['structure']['tables_fields'] as $tN => $d) {
				if (in_array($tN, $modify_tables)) {
					$infoArray['fields'][] = $tN . ': <i>' .
							(is_array($d['fields']) ? implode(', ', array_keys($d['fields'])) : '') .
							(is_array($d['keys']) ?
									' + ' . count($d['keys']) . ' ' . $GLOBALS['LANG']->getLL('detailedExtAnalysis_keys') : '') .
							'</i>';
					if (is_array($d['fields'])) {
						foreach ($d['fields'] as $fN => $value) {
							$infoArray['dump_tf'][] = $tN . '.' . $fN;
							if (!t3lib_div::isFirstPartOfStr($fN, $table_class_prefix)) {
								$infoArray['NSerrors']['fields'][$fN] = $fN;
							} else {
								$infoArray['NSok']['fields'][$fN] = $fN;
							}
						}
					}
					if (is_array($d['keys'])) {
						foreach ($d['keys'] as $fN => $value) {
							$infoArray['dump_tf'][] = $tN . '.KEY:' . $fN;
						}
					}
				} else {
					$infoArray['dump_tf'][] = $tN;
					$infoArray['tables'][] = $tN;
					if (!t3lib_div::isFirstPartOfStr($tN, $table_class_prefix)) {
						$infoArray['NSerrors']['tables'][$tN] = $tN;
					} else {
						$infoArray['NSok']['tables'][$tN] = $tN;
					}
				}
			}
			if (count($dbInfo['structure']['diff']['diff']) || count($dbInfo['structure']['diff']['extra'])) {
				$msg = array();
				if (count($dbInfo['structure']['diff']['diff'])) {
					$msg[] = $GLOBALS['LANG']->getLL('detailedExtAnalysis_tables_are_missing');
				}
				if (count($dbInfo['structure']['diff']['extra'])) {
					$msg[] = $GLOBALS['LANG']->getLL('detailedExtAnalysis_tables_are_of_wrong_type');
				}
				$infoArray['tables_error'] = 1;
				if (t3lib_extMgm::isLoaded($extKey)) {
					$infoArray['errors'][] = sprintf($GLOBALS['LANG']->getLL('detailedExtAnalysis_tables_are'),
						implode(' ' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:and') . ' ', $msg)
					);
				}
			}
		}

		// Static tables?
		if (is_array($dbInfo['static'])) {
			$infoArray['static'] = array_keys($dbInfo['static']);

			foreach ($dbInfo['static'] as $tN => $d) {
				if (!$d['exists']) {
					$infoArray['static_error'] = 1;
					if (t3lib_extMgm::isLoaded($extKey)) {
						$infoArray['errors'][] = $GLOBALS['LANG']->getLL('detailedExtAnalysis_static_tables_missing');
					}
					if (!t3lib_div::isFirstPartOfStr($tN, $table_class_prefix)) {
						$infoArray['NSerrors']['tables'][$tN] = $tN;
					} else {
						$infoArray['NSok']['tables'][$tN] = $tN;
					}
				}
			}
		}

		// Backend Module-check:
		$knownModuleList = t3lib_div::trimExplode(',', $extInfo['EM_CONF']['module'], 1);
		foreach ($knownModuleList as $mod) {
			if (@is_dir($absPath . $mod)) {
				if (@is_file($absPath . $mod . '/conf.php')) {
					$confFileInfo = $extensionDetails->modConfFileAnalysis($absPath . $mod . '/conf.php');
					if (is_array($confFileInfo['TYPO3_MOD_PATH'])) {
						$shouldBePath = tx_em_Tools::typeRelPath($extInfo['type']) . $extKey . '/' . $mod . '/';
						if (strcmp($confFileInfo['TYPO3_MOD_PATH'][1][1], $shouldBePath)) {
							$infoArray['errors'][] = sprintf($GLOBALS['LANG']->getLL('detailedExtAnalysis_wrong_mod_path'),
								$confFileInfo['TYPO3_MOD_PATH'][1][1],
								$shouldBePath
							);
						}
					} else {
						// It seems like TYPO3_MOD_PATH and therefore also this warning is no longer needed.
						// $infoArray['errors'][] = 'No definition of TYPO3_MOD_PATH constant found inside!';
					}
					if (is_array($confFileInfo['MCONF_name'])) {
						$mName = $confFileInfo['MCONF_name'][1][1];
						$mNameParts = explode('_', $mName);
						$infoArray['moduleNames'][] = $mName;
						if (!t3lib_div::isFirstPartOfStr($mNameParts[0], $module_prefix) &&
								(!$mNameParts[1] || !t3lib_div::isFirstPartOfStr($mNameParts[1], $module_prefix))) {
							$infoArray['NSerrors']['modname'][] = $mName;
						} else {
							$infoArray['NSok']['modname'][] = $mName;
						}
					} else {
						$infoArray['errors'][] = $GLOBALS['LANG']->getLL('detailedExtAnalysis_mconf_missing');
					}
				} else  {
					$infoArray['errors'][] = sprintf($GLOBALS['LANG']->getLL('detailedExtAnalysis_be_module_conf_missing'),
							$mod . '/conf.php'
					);
				}
			} else {
				$infoArray['errors'][] = sprintf($GLOBALS['LANG']->getLL('detailedExtAnalysis_module_folder_missing'),
						$mod . '/'
				);
			}
		}
		$dirs = t3lib_div::get_dirs($absPath);
		if (is_array($dirs)) {
			reset($dirs);
			while (list(, $mod) = each($dirs)) {
				if (!in_array($mod, $knownModuleList) && @is_file($absPath . $mod . '/conf.php')) {
					$confFileInfo = $extensionDetails->modConfFileAnalysis($absPath . $mod . '/conf.php');
					if (is_array($confFileInfo)) {
						$infoArray['errors'][] = sprintf($GLOBALS['LANG']->getLL('detailedExtAnalysis_unconfigured_module'),
								$mod . '/conf.php'
						);
					}
				}
			}
		}

		// ext_tables.php:
		if (@is_file($absPath . 'ext_tables.php')) {
			$content = t3lib_div::getUrl($absPath . 'ext_tables.php');
			if (stristr($content, 't3lib_extMgm::addModule')) {
				$infoArray['flags'][] = $GLOBALS['LANG']->getLL('detailedExtAnalysis_module');
			}
			if (stristr($content, 't3lib_extMgm::insertModuleFunction')) {
				$infoArray['flags'][] = $GLOBALS['LANG']->getLL('detailedExtAnalysis_module_and_more');
			}
			if (stristr($content, 't3lib_div::loadTCA')) {
				$infoArray['flags'][] = $GLOBALS['LANG']->getLL('detailedExtAnalysis_loadTCA');
			}
			if (stristr($content, '$TCA[')) {
				$infoArray['flags'][] = $GLOBALS['LANG']->getLL('detailedExtAnalysis_TCA');
			}
			if (stristr($content, 't3lib_extMgm::addPlugin')) {
				$infoArray['flags'][] = $GLOBALS['LANG']->getLL('detailedExtAnalysis_plugin');
			}
		}

		// ext_localconf.php:
		if (@is_file($absPath . 'ext_localconf.php')) {
			$content = t3lib_div::getUrl($absPath . 'ext_localconf.php');
			if (stristr($content, 't3lib_extMgm::addPItoST43')) {
				$infoArray['flags'][] = $GLOBALS['LANG']->getLL('detailedExtAnalysis_plugin_st43');
			}
			if (stristr($content, 't3lib_extMgm::addPageTSConfig')) {
				$infoArray['flags'][] = $GLOBALS['LANG']->getLL('detailedExtAnalysis_page_ts');
			}
			if (stristr($content, 't3lib_extMgm::addUserTSConfig')) {
				$infoArray['flags'][] = $GLOBALS['LANG']->getLL('detailedExtAnalysis_user_ts');
			}
			if (stristr($content, 't3lib_extMgm::addTypoScriptSetup')) {
				$infoArray['flags'][] = $GLOBALS['LANG']->getLL('detailedExtAnalysis_ts_setup');
			}
			if (stristr($content, 't3lib_extMgm::addTypoScriptConstants')) {
				$infoArray['flags'][] = $GLOBALS['LANG']->getLL('detailedExtAnalysis_ts_constants');
			}
		}

		if ($this->extensionHasCacheConfiguration($absPath)) {
			$infoArray['hasCacheConfiguration'] = TRUE;
		}

		if (@is_file($absPath . 'ext_typoscript_constants.txt')) {
			$infoArray['TSfiles'][] = $GLOBALS['LANG']->getLL('detailedExtAnalysis_constants');
		}
		if (@is_file($absPath . 'ext_typoscript_setup.txt')) {
			$infoArray['TSfiles'][] = $GLOBALS['LANG']->getLL('detailedExtAnalysis_setup');
		}
		if (@is_file($absPath . 'ext_conf_template.txt')) {
			$infoArray['conf'] = 1;
		}

		// Classes:
		if ($validity) {
			$filesInside = tx_em_Tools::getClassIndexLocallangFiles($absPath, $table_class_prefix, $extKey);
			if (is_array($filesInside['errors'])) {
				$infoArray['errors'] = array_merge((array) $infoArray['errors'], $filesInside['errors']);
			}
			if (is_array($filesInside['NSerrors'])) {
				$infoArray['NSerrors'] = array_merge((array) $infoArray['NSerrors'], $filesInside['NSerrors']);
			}
			if (is_array($filesInside['NSok'])) {
				$infoArray['NSok'] = array_merge((array) $infoArray['NSok'], $filesInside['NSok']);
			}
			$infoArray['locallang'] = $filesInside['locallang'];
			$infoArray['classes'] = $filesInside['classes'];
		}

		// Upload folders
		if ($extInfo['EM_CONF']['uploadfolder']) {
			$infoArray['uploadfolder'] = tx_em_Tools::uploadFolder($extKey);
			if (!@is_dir(PATH_site . $infoArray['uploadfolder'])) {
				$infoArray['errors'][] = sprintf($GLOBALS['LANG']->getLL('detailedExtAnalysis_no_upload_folder'),
					$infoArray['uploadfolder']
				);
				$infoArray['uploadfolder'] = '';
			}
		}

		// Create directories:
		if ($extInfo['EM_CONF']['createDirs']) {
			$infoArray['createDirs'] = array_unique(t3lib_div::trimExplode(',', $extInfo['EM_CONF']['createDirs'], 1));
			foreach ($infoArray['createDirs'] as $crDir) {
				if (!@is_dir(PATH_site . $crDir)) {
					$infoArray['errors'][] = sprintf($GLOBALS['LANG']->getLL('detailedExtAnalysis_no_upload_folder'),
						$crDir
					);
				}
			}
		}

		// Return result array:
		return $infoArray;
	}


	/**
	 * Produces the config form for an extension (if any template file, ext_conf_template.txt is found)
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array
	 * @param	boolean		If TRUE, the form HTML content is returned, otherwise the content is set in $this->content.
	 * @param	string		Submit-to URL (supposedly)
	 * @param	string		Additional form fields to include.
	 * @return	string		Depending on $output. Can return the whole form.
	 */
	function tsStyleConfigForm($extKey, $extInfo, $output = 0, $script = '', $addFields = '') {
		global $TYPO3_CONF_VARS;

			// Initialize:
		$absPath = tx_em_Tools::getExtPath($extKey, $extInfo['type']);
		$relPath = tx_em_Tools::typeRelPath($extInfo['type']) . $extKey . '/';

		$form = '';

			// Look for template file for form:
		if (t3lib_extMgm::isLoaded($extKey) && (@is_file($absPath . 'ext_conf_template.txt') || $this->extensionHasCacheConfiguration($absPath))) {
				// Load tsStyleConfig class and parse configuration template:
			$tsStyleConfig = t3lib_div::makeInstance('t3lib_tsStyleConfig');
			$tsStyleConfig->doNotSortCategoriesBeforeMakingForm = TRUE;
			$theConstants = $tsStyleConfig->ext_initTSstyleConfig(
				t3lib_div::getUrl($absPath . 'ext_conf_template.txt'),
				$relPath,
				$absPath,
				$GLOBALS['BACK_PATH']
			);

				// Load the list of resources.
			$tsStyleConfig->ext_loadResources($absPath . 'res/');

				// Load current value:
			$arr = unserialize($TYPO3_CONF_VARS['EXT']['extConf'][$extKey]);
			$arr = is_array($arr) ? $arr : array();

				// Call processing function for constants config and data before write and form rendering:
			if (is_array($TYPO3_CONF_VARS['SC_OPTIONS']['typo3/mod/tools/em/index.php']['tsStyleConfigForm'])) {
				$_params = array('fields' => &$theConstants, 'data' => &$arr, 'extKey' => $extKey);
				foreach ($TYPO3_CONF_VARS['SC_OPTIONS']['typo3/mod/tools/em/index.php']['tsStyleConfigForm'] as $_funcRef) {
					t3lib_div::callUserFunction($_funcRef, $_params, $this);
				}
				unset($_params);
			}

				// If saving operation is done:
			if (t3lib_div::_POST('submit')) {
				$tsStyleConfig->ext_procesInput(t3lib_div::_POST(), array(), $theConstants, array());
				$arr = $tsStyleConfig->ext_mergeIncomingWithExisting($arr);
				$this->writeTsStyleConfig($extKey, $arr);
			}

				// Setting value array
			$tsStyleConfig->ext_setValuearray($theConstants, $arr);

				// Getting session data:
			$MOD_MENU = array();
			$MOD_MENU['constant_editor_cat'] = $tsStyleConfig->ext_getCategoriesForModMenu();
			$MOD_SETTINGS = t3lib_BEfunc::getModuleData($MOD_MENU, t3lib_div::_GP('SET'), 'xMod_test');

				// Resetting the menu (stop)
			if (count($MOD_MENU['constant_editor_cat']) > 1) {
				$menu = $GLOBALS['LANG']->getLL('extInfoArray_category') . ' ' .
						t3lib_BEfunc::getFuncMenu(0, 'SET[constant_editor_cat]', $MOD_SETTINGS['constant_editor_cat'], $MOD_MENU['constant_editor_cat'], '', '&CMD[showExt]=' . $extKey);
					// add class to select
				$menu = str_replace('<select', '<select class="mod-menu-template-select"', $menu);
				if ($this->silentMode) {
					$menu = str_replace('jumpToUrl', 'abc', $menu);
				}

				if ($this->parentObject instanceof SC_mod_tools_em_index) {
					$this->parentObject->content .= $this->parentObject->doc->section('', '<span class="nobr">' . $menu . '</span>');
					$this->parentObject->content .= $this->parentObject->doc->spacer(10);
				} else {
					$form .= '<h3>' . '<span class="nobr">' . $menu . '</span></h3>';
				}
			}

				// Category and constant editor config:
			$form .= '
				<table border="0" cellpadding="0" cellspacing="0" width="600">
					<tr>
						<td>' . $tsStyleConfig->ext_getForm(
							$MOD_SETTINGS['constant_editor_cat'],
							$theConstants,
							$script,
							$addFields,
							$extKey,
							!$this->silentMode
						) . ($this->silentMode ? '' : '</form>') .'</td>
					</tr>
				</table>';
		} else {
			if (!$this->silentMode) {
				$flashMessage = t3lib_div::makeInstance(
					't3lib_FlashMessage',
					$GLOBALS['LANG']->getLL('tsStyleConfigForm_additional_config'),
					'',
					t3lib_FlashMessage::INFO
				);
			}

			$form = '
				<table border="0" cellpadding="0" cellspacing="0" width="600">
					<tr>
						<td>
							<form action="' . htmlspecialchars($script) . '" method="post">' .
					$addFields .
					($this->silentMode ? '' : $flashMessage->render()) .
					(t3lib_div::_GP('nodoc') ? '<input type="hidden" name="nodoc" value="1" />' : '') .
					'<br /><input type="submit" id="configuration-submit-' . $extKey . '" name="write" value="' . $GLOBALS['LANG']->getLL('updatesForm_make_updates') . '" />
							</form>
						</td>
					</tr>
				</table>';
		}

		return $form;
	}

	/**
	 * Determine if an extension defines own caching framework caches
	 *
	 * @param string $extensionAbsolutePath Path to extension
	 * @return boolean TRUE if extension defines own caches
	 */
	protected function extensionHasCacheConfiguration($extensionAbsolutePath) {
		$result = FALSE;
		if (@is_file($extensionAbsolutePath . 'ext_localconf.php')) {
			$content = t3lib_div::getUrl($extensionAbsolutePath . 'ext_localconf.php');
			if (stristr($content, 'cacheConfigurations')) {
				$result = TRUE;
			}
		}
		return $result;
	}

	/**
	 * Writes the TSstyleconf values to "localconf.php"
	 * Removes the temp_CACHED* files before return.
	 *
	 * @param	string		Extension key
	 * @param	array		Configuration array to write back
	 * @return	void
	 */
	function writeTsStyleConfig($extKey, $arr) {

		// Instance of install tool
		$instObj = t3lib_div::makeInstance('t3lib_install');
		$instObj->allowUpdateLocalConf = 1;
		$instObj->updateIdentity = 'TYPO3 Extension Manager';

		// Get lines from localconf file
		$lines = $instObj->writeToLocalconf_control();
		$instObj->setValueInLocalconfFile($lines, '$TYPO3_CONF_VARS[\'EXT\'][\'extConf\'][\'' . $extKey . '\']', serialize($arr)); // This will be saved only if there are no linebreaks in it !
		$instObj->writeToLocalconf_control($lines);

		t3lib_extMgm::removeCacheFiles();
	}


	/**
	 * Creates a form for an extension which contains all options for configuration, updates of database, clearing of cache etc.
	 *
	 * @param  string  $extKey  Extension key
	 * @param  array  $extInfo  Extension information array
	 * @param  boolean  $notSilent  If set, the form will ONLY show if fields/tables should be updated (suppressing forms like general configuration and cache clearing).
	 * @param  string  $script  Alternative action=""-script
	 * @param  string  $addFields  Additional form fields
	 * @param  boolean  $addFormTags  TRUE if it shopuld be wrapped with form tag
	 * @param  boolean  $excludeDatabaseCheck  TRUE if no database check should be done
	 * @return  string
	 */
	function updatesForm($extKey, $extInfo, $notSilent = FALSE, $script = '', $addFields = '', $addFormTag = TRUE, $excludeDatabaseCheck = FALSE) {
		$script = $script ? $script : t3lib_div::linkThisScript();
		if ($addFormTag) {
			$formWrap = array('<form action="' . htmlspecialchars($script) . '" method="POST">', '</form>');
		} else {
			$formWrap = array('', '');
		}
		$extensionDetails = t3lib_div::makeInstance('tx_em_Extensions_Details', $this);
		$updates = '';

		if (!$excludeDatabaseCheck) {
			$updates .= $this->checkDBupdates($extKey, $extInfo);
		}

		$uCache = $this->checkClearCache($extInfo);
		if ($notSilent) {
			$updates .= $uCache;
		}
		$updates .= $extensionDetails->checkUploadFolder($extKey, $extInfo);

		$absPath = tx_em_Tools::getExtPath($extKey, $extInfo['type']);
		if ($notSilent && @is_file($absPath . 'ext_conf_template.txt') || $this->extensionHasCacheConfiguration($absPath)) {
			$configForm = $this->tsStyleConfigForm($extKey, $extInfo, 1, $script, $updates . $addFields . '<br />');
		}

		if ($updates || $configForm) {
			if ($configForm) {
				$updates =  $formWrap[0] . $configForm . $formWrap[1];
			} else {
				$updates = $formWrap[0] . $updates . $addFields . '
					<br /><input type="submit" name="write" id="update-submit-' . $extKey . '" value="' . $GLOBALS['LANG']->getLL('updatesForm_make_updates') . '" />
				' . $formWrap[1];
			}
		}
		return $updates;
	}


	/**
	 * Check if clear-cache should be performed, otherwise show form (for installation of extension)
	 * Shown only if the extension has the clearCacheOnLoad flag set.
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array
	 * @return	string		HTML output (if form is shown)
	 */
	function checkClearCache($extInfo) {
		$content = '';
		if ($extInfo['EM_CONF']['clearCacheOnLoad']) {
			if (t3lib_div::_POST('_clear_all_cache')) { // Action: Clearing the cache
				$tce = t3lib_div::makeInstance('t3lib_TCEmain');
				$tce->stripslashes_values = 0;
				$tce->start(array(), array());
				$tce->clear_cacheCmd('all');
			} else { // Show checkbox for clearing cache:
				$content .= '
					<br />
					<h3>' . $GLOBALS['LANG']->getLL('checkUploadFolder_clear_cache') . '</h3>
					<p>' . $GLOBALS['LANG']->getLL('checkUploadFolder_clear_cache_requested') . '<br />
						<label for="check_clear_all_cache">' . $GLOBALS['LANG']->getLL('checkUploadFolder_clear_all_cache') . '</label>
						<input type="checkbox" name="_clear_all_cache" id="check_clear_all_cache" checked="checked" value="1" /><br />
					</p>
				';
			}
		}
		return $content;
	}

	/**
	 * Writes the extension list to "localconf.php" file
	 * Removes the temp_CACHED* files before return.
	 *
	 * @param	string		List of extensions
	 * @return	void
	 */
	function writeNewExtensionList($newExtList) {
		$strippedExtensionList = $this->stripNonFrontendExtensions($newExtList);

		// Instance of install tool
		$instObj = t3lib_div::makeInstance('t3lib_install');
		$instObj->allowUpdateLocalConf = 1;
		$instObj->updateIdentity = 'TYPO3 Extension Manager';

		// Get lines from localconf file
		$lines = $instObj->writeToLocalconf_control();
		$instObj->setValueInLocalconfFile($lines, '$TYPO3_CONF_VARS[\'EXT\'][\'extList\']', $newExtList);
		$instObj->setValueInLocalconfFile($lines, '$TYPO3_CONF_VARS[\'EXT\'][\'extList_FE\']', $strippedExtensionList);
		$instObj->writeToLocalconf_control($lines);

		$GLOBALS['TYPO3_CONF_VARS']['EXT']['extList'] = $newExtList;
		$GLOBALS['TYPO3_CONF_VARS']['EXT']['extList_FE'] = $strippedExtensionList;
		t3lib_extMgm::removeCacheFiles();
		$GLOBALS['typo3CacheManager']->getCache('cache_phpcode')->flushByTag('t3lib_autoloader');
	}

	/**
	 * Removes unneeded extensions from the frontend based on
	 * EMCONF doNotLoadInFE = 1
	 *
	 * @param string $extList
	 * @return string
	 */
	function stripNonFrontendExtensions($extList) {
		$fullExtList = $this->parentObject->extensionList->getInstalledExtensions();
		$extListArray = t3lib_div::trimExplode(',', $extList);
		foreach ($extListArray as $arrayKey => $extKey) {
			if ($fullExtList[0][$extKey]['EM_CONF']['doNotLoadInFE'] == 1) {
				unset($extListArray[$arrayKey]);
			}
		}
		$nonFEList = implode(',', $extListArray);
		return $nonFEList;
	}

	/**
	 * Updates the database according to extension requirements
	 * DBAL compliant (based on Install Tool code)
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array
	 * @return	void
	 */
	function forceDBupdates($extKey, $extInfo) {
		// Updating tables and fields?
		if (is_array($extInfo['files']) && in_array('ext_tables.sql', $extInfo['files'])) {
				// Update the cache configurations with information of new extensions,
				// that have been installed during the current process
			$GLOBALS['typo3CacheManager']->setCacheConfigurations(
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']
			);

			$fileContent = t3lib_div::getUrl(tx_em_Tools::getExtPath($extKey, $extInfo['type']) . 'ext_tables.sql');
			$fileContent .= t3lib_cache::getDatabaseTableDefinitions();

			$FDfile = $this->installerSql->getFieldDefinitions_fileContent($fileContent);
			if (count($FDfile)) {
				$FDdb = $this->installerSql->getFieldDefinitions_database(TYPO3_db);
				$diff = $this->installerSql->getDatabaseExtra($FDfile, $FDdb);
				$update_statements = $this->installerSql->getUpdateSuggestions($diff);

				foreach ((array) $update_statements['add'] as $string) {
					$GLOBALS['TYPO3_DB']->admin_query($string);
				}
				foreach ((array) $update_statements['change'] as $string) {
					$GLOBALS['TYPO3_DB']->admin_query($string);
				}
				foreach ((array) $update_statements['create_table'] as $string) {
					$GLOBALS['TYPO3_DB']->admin_query($string);
				}
			}
		}

		// Importing static tables?
		if (is_array($extInfo['files']) && in_array('ext_tables_static+adt.sql', $extInfo['files'])) {
			$fileContent = t3lib_div::getUrl(tx_em_Tools::getExtPath($extKey, $extInfo['type']) . 'ext_tables_static+adt.sql');

			$statements = $this->installerSql->getStatementarray($fileContent, 1);
			list($statements_table, $insertCount) = $this->installerSql->getCreateTables($statements, 1);

			// Traverse the tables
			foreach ($statements_table as $table => $query) {
				$GLOBALS['TYPO3_DB']->admin_query('DROP TABLE IF EXISTS ' . $table);
				$GLOBALS['TYPO3_DB']->admin_query($query);

				if ($insertCount[$table]) {
					$statements_insert = $this->installerSql->getTableInsertStatements($statements, $table);

					foreach ($statements_insert as $v) {
						$GLOBALS['TYPO3_DB']->admin_query($v);
					}
				}
			}
		}
	}

}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/sysext/em/classes/install/class.tx_em_install.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/sysext/em/classes/install/class.tx_em_install.php']);
}

?>