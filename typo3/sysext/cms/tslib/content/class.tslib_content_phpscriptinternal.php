<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2011 Xavier Perseguers <typo3@perseguers.ch>
 *  (c) 2010-2011 Steffen Kamper <steffen@typo3.org>
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
 * Contains PHP_SCRIPT_INT class object.
 *
 * @author Xavier Perseguers <typo3@perseguers.ch>
 * @author Steffen Kamper <steffen@typo3.org>
 */
class tslib_content_PhpScriptInternal extends tslib_content_Abstract {

	/**
	 * Rendering the cObject, PHP_SCRIPT_INT and PHP_SCRIPT_EXT
	 *
	 * @param	array		Array of TypoScript properties
	 * @return	string		Output
	 * @deprecated since TYPO3 4.6, will be removed in TYPO3 4.8
	 */
	public function render($conf = array()) {
		$GLOBALS['TSFE']->logDeprecatedTyposcript(
			'PHP_SCRIPT_INT',
			'Usage of PHP_SCRIPT_INT is deprecated since TYPO3 4.6. Use plugins instead.'
		);
		$file = isset($conf['file.'])
			? $this->cObj->stdWrap($conf['file'], $conf['file.'])
			: $conf['file'];

		$incFile = $GLOBALS['TSFE']->tmpl->getFileName($file);
		$content = '';
		if ($incFile && $GLOBALS['TSFE']->checkFileInclude($incFile)) {
			$substKey = 'INT_SCRIPT.' . $GLOBALS['TSFE']->uniqueHash();
			$content .= '<!--' . $substKey . '-->';
			$GLOBALS['TSFE']->config['INTincScript'][$substKey] = array (
				'file' => $incFile, 'conf' => $conf, 'type' => 'SCRIPT'
			);
			$GLOBALS['TSFE']->config['INTincScript'][$substKey]['cObj'] = serialize($this->cObj);
		}

		if (isset($conf['stdWrap.'])) {
			$content = $this->cObj->stdWrap($content, $conf['stdWrap.']);
		}

		return $content;
	}

}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['tslib/content/class.tslib_content_phpscriptinternal.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['tslib/content/class.tslib_content_phpscriptinternal.php']);
}

?>