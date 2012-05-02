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
 * Contains FILE class object.
 *
 * @author Xavier Perseguers <typo3@perseguers.ch>
 * @author Steffen Kamper <steffen@typo3.org>
 */
class tslib_content_File extends tslib_content_Abstract {

	/**
	 * Rendering the cObject, FILE
	 *
	 * @param	array		Array of TypoScript properties
	 * @return	string		Output
	 */
	public function render($conf = array()) {

		$file = isset($conf['file.'])
			? $this->cObj->stdWrap($conf['file'], $conf['file.'])
			: $conf['file'];

		$theValue = $this->cObj->fileResource($file, trim($this->cObj->getAltParam($conf, FALSE)));

		$linkWrap =  isset($conf['linkWrap.'])
			? $this->cObj->stdWrap($conf['linkWrap'], $conf['linkWrap.'])
			: $conf['linkWrap'];
		if ($linkWrap) {
			$theValue = $this->cObj->linkWrap($theValue, $linkWrap);
		}

		$wrap =  isset($conf['wrap.'])
			? $this->cObj->stdWrap($conf['wrap'], $conf['wrap.'])
			: $conf['wrap'];
		if ($wrap) {
			$theValue = $this->cObj->wrap($theValue, $wrap);
		}

		if (isset($conf['stdWrap.'])) {
			$theValue = $this->cObj->stdWrap($theValue, $conf['stdWrap.']);
		}

		return $theValue;

	}

}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['tslib/content/class.tslib_content_file.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['tslib/content/class.tslib_content_file.php']);
}

?>