<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Steffen Kamper <steffen@typo3.org>
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

class tx_em_API {

	/**
	 * @var array
	 */
	public $typeLabels = array();

	/**
	 * @var array
	 */
	public $typeDescr = array();

	/**
	 * @var array
	 */
	public $typeBackPaths = array();


	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {

		// load langauge file
		$GLOBALS['LANG']->includeLLFile(t3lib_extMgm::extPath('em') . 'language/locallang.xml');

		/**
		 * "TYPE" information; labels, paths, description etc.
		 */
		$this->typeLabels = array(
			'S' => $GLOBALS['LANG']->getLL('type_system'),
			'G' => $GLOBALS['LANG']->getLL('type_global'),
			'L' => $GLOBALS['LANG']->getLL('type_local'),
		);
		$this->typeDescr = array(
			'S' => $GLOBALS['LANG']->getLL('descr_system'),
			'G' => $GLOBALS['LANG']->getLL('descr_global'),
			'L' => $GLOBALS['LANG']->getLL('descr_local'),
		);



	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/sysext/em/classes/class.tx_em_api.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/sysext/em/classes/class.tx_em_api.php']);
}

?>