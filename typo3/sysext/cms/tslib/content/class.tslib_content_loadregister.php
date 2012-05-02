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
 * Contains LOAD_REGISTER class object.
 *
 * @author Xavier Perseguers <typo3@perseguers.ch>
 * @author Steffen Kamper <steffen@typo3.org>
 */
class tslib_content_LoadRegister extends tslib_content_Abstract {

	/**
	 * Rendering the cObject, LOAD_REGISTER
	 * NOTICE: This cObject does NOT return any content since it just sets internal data based on the TypoScript properties.
	 *
	 * @param	array		Array of TypoScript properties
	 * @return	string		Empty string (the cObject only sets internal data!)
	 */
	public function render($conf = array()) {
		array_push($GLOBALS['TSFE']->registerStack, $GLOBALS['TSFE']->register);

		if (is_array($conf)) {
			$isExecuted = array();
			foreach ($conf as $theKey => $theValue) {
				$register = rtrim($theKey, '.');
				if(!$isExecuted[$register]) {
					$registerProperties = $register . '.';
					if(isset($conf[$register]) && isset($conf[$registerProperties])) {
						$theValue = $this->cObj->stdWrap($conf[$register], $conf[$registerProperties]);
					} elseif(isset($conf[$registerProperties])) {
						$theValue = $this->cObj->stdWrap('', $conf[$registerProperties]);
					}
					$GLOBALS['TSFE']->register[$register] = $theValue;
					$isExecuted[$register] = TRUE;
				}
			}
		}
		return '';
	}

}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['tslib/content/class.tslib_content_loadregister.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['tslib/content/class.tslib_content_loadregister.php']);
}

?>