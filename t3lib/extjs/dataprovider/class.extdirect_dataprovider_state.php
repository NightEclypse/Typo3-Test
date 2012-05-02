<?php
/***************************************************************
 *  Copyright notice
 *
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
 * ExtDirect DataProvider for State
 */
class extDirect_DataProvider_State {

	/** @var extDirect_DataProvider_BackenduserSettings */
	protected $userSettings;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
			//all data is saved in BE_USER->uc
		$this->userSettings = t3lib_div::makeInstance('extDirect_DataProvider_BackenduserSettings');
	}

	/**
	 * Gets state for given key
	 *
	 * @param  stdClass $parameter
	 * @return array
	 */
	public function getState($parameter) {
		$key = $parameter->params->key;
		$data = $this->userSettings->get($key);

		return array(
			'success' => TRUE,
			'data' =>  $data
		);
	}

	/**
	 * Save the state for a given key
	 *
	 * @param  stdClass $parameter
	 * @return array
	 */
	public function setState($parameter) {
		$key = $parameter->params->key;
		$data = json_decode($parameter->params->data);

		$this->userSettings->set($key . '.' . $data[0]->name, $data[0]->value);
		return array(
			'success' => TRUE,
			'params' => $parameter
		);
	}


}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/extjs/dataprovider/class.extdirect_dataprovider_state.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/extjs/dataprovider/class.extdirect_dataprovider_state.php']);
}

?>