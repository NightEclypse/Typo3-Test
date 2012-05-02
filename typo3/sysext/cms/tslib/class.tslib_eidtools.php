<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Tools for scripts using the eID feature of index.php
 * Included from index_ts.php
 * Since scripts using the eID feature does not
 * have a full FE environment initialized by default
 * this class seeks to provide functions that can
 * initialize parts of the FE environment as needed,
 * eg. Frontend User session, Database connection etc.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */

/**
 * Tools for scripts using the eID feature of index.php
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	Dmitry Dulepov <dmitry@typo3.org>
 * @package TYPO3
 * @subpackage tslib
 */
final class tslib_eidtools {

	/**
	 * Load and initialize Frontend User. Note, this process is slow because
	 * it creates a calls many objects. Call this method only if necessary!
	 *
	 * @return	object		Frontend User object (usually known as TSFE->fe_user)
	 */
	public static function initFeUser()	{
		// Initialize the database. Do not use TSFE method as it may redirect to
		// Install tool and call hooks, which do not expect to be called from eID
		self::connectDB();

		// Get TSFE instance. It knows how to initialize the user. We also
		// need TCA because services may need extra tables!
		self::initTCA();
		$tsfe = self::getTSFE();
		/* @var $tsfe tslib_fe */

		$tsfe->initFEuser();

		// Return FE user object:
		return $tsfe->fe_user;
	}

	/**
	 * Connecting to database. If the function fails, last error message
	 * can be retrieved using $GLOBALS['TYPO3_DB']->sql_error().
	 *
	 * @return	boolean		TRUE if connection was successful
	 */
	public static function connectDB()	{
		if (!$GLOBALS['TYPO3_DB']->isConnected()) {
				// Attempt to connect to the database
			$GLOBALS['TYPO3_DB']->connectDB();
		}
			// connectDB() throws exceptions if something went wrong,
			// so we are sure that connect was successful here.
		return TRUE;
	}

	/**
	 * Initializes $GLOBALS['LANG'] for use in eID scripts.
	 *
	 * @param	string		$language	TYPO3 language code
	 * @return	void
	 */
	public static function initLanguage($language = 'default') {
		if (!is_object($GLOBALS['LANG'])) {
			$GLOBALS['LANG'] = t3lib_div::makeInstance('language');
			$GLOBALS['LANG']->init($language);
		}
	}

	/**
	 * Makes TCA available inside eID
	 *
	 * @return	void
	 */
	public static function initTCA() {
		// Some badly made extensions attempt to manipulate TCA in a wrong way
		// (inside ext_localconf.php). Therefore $GLOBALS['TCA'] may become an array
		// but in fact it is not loaded. The check below ensure that
		// TCA is still loaded if such bad extensions are installed
		if (!is_array($GLOBALS['TCA']) || !isset($GLOBALS['TCA']['pages'])) {
			// Load TCA using TSFE
			self::getTSFE()->includeTCA(FALSE);
		}
	}

	/**
	 * Makes TCA for the extension available inside eID. Use this function if
	 * you need not to include the whole $GLOBALS['TCA']. However, you still need to call
	 * t3lib_div::loadTCA() if you want to access column array!
	 *
	 * @param	string		$extensionKey	Extension key
	 * @return	void
	 */
	public static function initExtensionTCA($extensionKey) {
		$extTablesPath = t3lib_extMgm::extPath($extensionKey, 'ext_tables.php');
		if (file_exists($extTablesPath)) {
			$GLOBALS['_EXTKEY'] = $extensionKey;
			require_once($extTablesPath);
			unset($GLOBALS['_EXTKEY']);
			// We do not need to save restore the value of $GLOBALS['_EXTKEY']
			// because it is not defined to anything real outside of
			// ext_tables.php or ext_localconf.php scope.
		}
	}

	/**
	 * Creating a single static cached instance of TSFE to use with this class.
	 *
	 * @return	tslib_fe		New instance of tslib_fe
	 */
	private static function getTSFE() {
		// Cached instance
		static $tsfe = NULL;

		if (is_null($tsfe)) {
			$tsfe = t3lib_div::makeInstance('tslib_fe', $GLOBALS['TYPO3_CONF_VARS'], 0, 0);
		}

		return $tsfe;
	}
}

?>