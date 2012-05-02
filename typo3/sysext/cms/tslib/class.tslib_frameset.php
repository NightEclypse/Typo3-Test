<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 1999-2012 Kasper Skårhøj (kasperYYYY@typo3.com)
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the textfile GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Rendering of framesets
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tslib
 */
class tslib_frameset {

	/**
	 * Generates a frameset based on input configuration in a TypoScript array.
	 *
	 * @param	array		The TypoScript properties of the PAGE object property "frameSet.". See link.
	 * @return	string		A <frameset> tag.
	 * @see TSpagegen::renderContentWithHeader()
	 */
	function make($setup) {
		$content = '';
		if (is_array($setup)) {
			$sKeyArray = t3lib_TStemplate::sortedKeyList($setup);
			foreach ($sKeyArray as $theKey) {
				$theValue = $setup[$theKey];
				if (intval($theKey) && $conf = $setup[$theKey . '.']) {
					switch ($theValue) {
						case 'FRAME' :
							$typeNum = intval($GLOBALS['TSFE']->tmpl->setup[$conf['obj'] . '.']['typeNum']);
							if (!$conf['src'] && !$typeNum) {
								$typeNum = -1;
							}
							$content .= '<frame' . $this->frameParams($conf, $typeNum) . ' />' . LF;
						break;
						case 'FRAMESET' :
							$frameset = t3lib_div::makeInstance('tslib_frameset');
							$content .= $frameset->make($conf) . LF;
						break;
					}
				}
			}
			return '<frameset' . $this->framesetParams($setup) . '>' . LF . $content . '</frameset>';
		}
	}

	/**
	 * Creates the attributes for a <frame> tag based on a $conf array and the type number
	 *
	 * @param	array		Configuration for the parameter generation for the FRAME set. See link
	 * @param	integer		The typenumber to use for the link.
	 * @return	string		String with attributes for the frame-tag. With a prefixed space character.
	 * @access private
	 * @link http://typo3.org/documentation/document-library/references/doc_core_tsref/current/view/7/9/
	 * @see make(), t3lib_TStemplate::linkData()
	 */
	function frameParams($setup, $typeNum) {
		$paramStr = '';
		$name = $setup['obj'];

		if ($setup['src'] || $setup['src.']) {
			$src = $setup['src'];
			if (is_array($setup['src.'])) {
				$src = $GLOBALS['TSFE']->cObj->stdWrap($src, $setup['src.']);
			}
			$paramStr .= ' src="' . htmlspecialchars($src) . '"';
		} else {
			$LD = $GLOBALS['TSFE']->tmpl->linkData(
				$GLOBALS['TSFE']->page,
				'',
				$GLOBALS['TSFE']->no_cache,
				'',
				'',
				($setup['options'] ? '&' . $setup['options'] : '') .
					$GLOBALS['TSFE']->cObj->getClosestMPvalueForPage($GLOBALS['TSFE']->page['uid']), intval($typeNum)
			);
			$finalURL = $LD['totalURL'];
			$paramStr .= ' src="' . htmlspecialchars($finalURL) . '"';
		}
		if ($setup['name']) {
			$paramStr .= ' name="' . $setup['name'] . '"';
		} else {
			$paramStr .= ' name="' . $name . '"';
		}
		if ($setup['params']) {
			$paramStr .= ' ' . $setup['params'];
		}
		return $paramStr;
	}

	/**
	 * Creates the attributes for a <frameset> tag based on a conf array($setup)
	 *
	 * @param	array		The setup array(TypoScript properties)
	 * @return	string		Attributes with preceeding space.
	 * @access private
	 * @see make()
	 */
	function framesetParams($setup) {
		$paramStr = '';
		if ($setup['cols']) {
			$paramStr .= ' cols="' . $setup['cols'] . '"';
		}
		if ($setup['rows']) {
			$paramStr .= ' rows="' . $setup['rows'] . '"';
		}
		if ($setup['params']) {
			$paramStr .= ' ' . $setup['params'];
		}
		return $paramStr;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['tslib/class.tslib_frameset.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['tslib/class.tslib_frameset.php']);
}

?>