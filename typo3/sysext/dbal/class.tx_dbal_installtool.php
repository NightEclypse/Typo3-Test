<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2011 Xavier Perseguers <xavier@typo3.org>
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
 * Hooks for TYPO3 Install Tool.
 *
 * @author Xavier Perseguers <xavier@typo3.org>
 *
 * @package TYPO3
 * @subpackage dbal
 */
class tx_dbal_installtool {

	/**
	 * @var string
	 */
	protected $templateFilePath = 'res/Templates/';

	/**
	 * @var array
	 */
	protected $supportedDrivers;

	/**
	 * @var array
	 */
	protected $availableDrivers;

	/**
	 * @var string
	 */
	protected $driver;

	/**
	 * Default constructor.
	 */
	public function __construct() {
		$this->supportedDrivers = $this->getSupportedDrivers();
		$this->availableDrivers = $this->getAvailableDrivers();

		$configDriver =& $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['dbal']['handlerCfg']['_DEFAULT']['config']['driver'];
		$this->driver = t3lib_div::_GET('driver');
		if (!$this->driver && $configDriver) {
			$this->driver = $configDriver;
		}
	}

	/**
	 * Hooks into Installer to set required PHP modules.
	 *
	 * @param array $modules
	 * @param tx_install|tx_reports_reports_status_SystemStatus $instObj
	 * @return array modules
	 */
	public function setRequiredPhpModules(array &$modules, $instObj) {
		$modifiedModules = array();
		foreach ($modules as $key => $module) {
			if ($module === 'mysql') {
				$dbModules = array();
				foreach ($this->supportedDrivers as $abstractionLayer => $drivers) {
					$dbModules = array_merge($dbModules, array_keys($drivers));
				}
				$module = $dbModules;
			}
			$modifiedModules[] = $module;
		}
		return $modifiedModules;
	}

	/**
	 * Hooks into Installer to let a non-MySQL database to be configured.
	 *
	 * @param array $markers
	 * @param integer $step
	 * @param tx_install $instObj
	 * @return void
	 */
	public function executeStepOutput(array &$markers, $step, tx_install $instObj) {
		switch ($step) {
			case 2:
				$this->createConnectionForm($markers, $instObj);
				break;
			case 3:
				$this->createDatabaseForm($markers, $instObj);
				break;
		}
	}

	/**
	 * Hooks into Installer to modify lines to be written to localconf.php.
	 *
	 * @param array $lines
	 * @param integer $step
	 * @param tx_install $instObj
	 * @return void
	 */
	public function executeWriteLocalconf(array &$lines, $step, tx_install $instObj) {
		switch ($step) {
			case 3:
			case 4:
				$driver = $instObj->INSTALL['localconf.php']['typo_db_driver'];
				if (!$driver && $this->driver) {
					// Driver was already configured
					break;
				}
				$driverConfig = '';
				switch ($driver) {
					case 'oci8':
						$driverConfig = '\'driverOptions\' => array(' .
								'\'connectSID\' => ' . ($instObj->INSTALL['localconf.php']['typo_db_type'] === 'sid' ? 'TRUE' : 'FALSE') .
								')';
						break;
					case 'mssql':
					case 'odbc_mssql':
						$driverConfig = '\'useNameQuote\' => TRUE,'
								. '\'quoteClob\' => FALSE';
						break;
					case 'mysql':
						return;
				}
				$config = 'array(' .
						'\'_DEFAULT\' => array(' .
						'\'type\' => \'adodb\',' .
						'\'config\' => array(' .
						'\'driver\' => \'' . $driver . '\',' .
						$driverConfig .
						')' .
						')' .
						');';
				$instObj->setValueInLocalconfFile($lines, '$TYPO3_CONF_VARS[\'EXTCONF\'][\'dbal\'][\'handlerCfg\']', $config, FALSE);
				break;
		}
	}

	/**
	 * Creates a specialized form to configure the DBMS connection.
	 *
	 * @param array $markers
	 * @param tx_install $instObj
	 * @return void
	 */
	protected function createConnectionForm(array &$markers, tx_install $instObj) {
		// Normalize current driver
		if (!$this->driver) {
			$this->driver = $this->getDefaultDriver();
		}

		// Get the template file
		$templateFile = @file_get_contents(
			t3lib_extMgm::extPath('dbal') . $this->templateFilePath . 'install.html'
		);
		// Get the template part from the file
		$template = t3lib_parsehtml::getSubpart(
			$templateFile, '###TEMPLATE###'
		);

		// Get the subpart for the connection form
		$formSubPart = t3lib_parsehtml::getSubpart(
			$template, '###CONNECTION_FORM###'
		);
		if ($this->getNumberOfAvailableDrivers() == 1 && $this->getDefaultDriver() === 'mysql') {
			// Only MySQL is actually available (PDO support may be compiled in
			// PHP itself and as such DBAL was activated, behaves as if DBAL were
			// not activated
			$driverSubPart = '<input type="hidden" name="TYPO3_INSTALL[localconf.php][typo_db_driver]" value="mysql" />';
		} else {
			$driverTemplate = t3lib_parsehtml::getSubpart(
				$formSubPart, '###DATABASE_DRIVER###'
			);
			$driverSubPart = $this->prepareDatabaseDrivers($driverTemplate);
		}
		$formSubPart = t3lib_parsehtml::substituteSubpart(
			$formSubPart,
			'###DATABASE_DRIVER###',
			$driverSubPart
		);

		// Get the subpart related to selected database driver
		if ($this->driver === '' || $this->driver === 'mysql') {
			$driverOptionsSubPart = t3lib_parsehtml::getSubpart(
				$template, '###DRIVER_MYSQL###'
			);
		} else {
			$driverOptionsSubPart = t3lib_parsehtml::getSubpart(
				$template, '###DRIVER_' . t3lib_div::strtoupper($this->driver) . '###'
			);
			if ($driverOptionsSubPart === '') {
				$driverOptionsSubPart = t3lib_parsehtml::getSubpart(
					$template, '###DRIVER_DEFAULT###'
				);
			}
		}

		// Define driver-specific markers
		$driverMarkers = array();
		switch ($this->driver) {
			case 'mssql':
				$driverMarkers = array(
					'labelUsername' => 'Username',
					'username' => TYPO3_db_username,
					'labelPassword' => 'Password',
					'password' => TYPO3_db_password,
					'labelHost' => 'Host',
					'host' => TYPO3_db_host ? TYPO3_db_host : 'windows',
					'labelDatabase' => 'Database',
					'database' => TYPO3_db,
				);
				$nextStep = $instObj->step + 2;
				break;
			case 'odbc_mssql':
				$driverMarkers = array(
					'labelUsername' => 'Username',
					'username' => TYPO3_db_username,
					'labelPassword' => 'Password',
					'password' => TYPO3_db_password,
					'labelHost' => 'Host',
					'host' => TYPO3_db_host ? TYPO3_db_host : 'windows',
					'database' => 'dummy_string',
				);
				$nextStep = $instObj->step + 2;
				break;
			case 'oci8':
				$driverMarkers = array(
					'labelUsername' => 'Username',
					'username' => TYPO3_db_username,
					'labelPassword' => 'Password',
					'password' => TYPO3_db_password,
					'labelHost' => 'Host',
					'host' => TYPO3_db_host ? TYPO3_db_host : 'localhost',
					'labelType' => 'Type',
					'labelSID' => 'SID',
					'labelServiceName' => 'Service Name',
					'labelDatabase' => 'Name',
					'database' => TYPO3_db,
				);
				$nextStep = $instObj->step + 2;
				break;
			case 'postgres':
				$driverMarkers = array(
					'labelUsername' => 'Username',
					'username' => TYPO3_db_username,
					'labelPassword' => 'Password',
					'password' => TYPO3_db_password,
					'labelHost' => 'Host',
					'host' => TYPO3_db_host ? TYPO3_db_host : 'localhost',
					'labelDatabase' => 'Database',
					'database' => TYPO3_db,
				);
				$nextStep = $instObj->step + 2;
				break;
			default:
				$driverMarkers = array(
					'labelUsername' => 'Username',
					'username' => TYPO3_db_username,
					'labelPassword' => 'Password',
					'password' => TYPO3_db_password,
					'labelHost' => 'Host',
					'host' => TYPO3_db_host ? TYPO3_db_host : 'localhost',
					'labelDatabase' => 'Database',
					'database' => TYPO3_db,
				);
				$nextStep = $instObj->step + 1;
				break;
		}

		// Add header marker for main template
		$markers['header'] = 'Connect to your database host';
		// Define the markers content for the subpart
		$subPartMarkers = array(
			'step' => $nextStep,
			'action' => htmlspecialchars($instObj->action),
			'encryptionKey' => $instObj->createEncryptionKey(),
			'branch' => TYPO3_branch,
			'driver_options' => $driverOptionsSubPart,
			'continue' => 'Continue',
			'llDescription' => 'If you have not already created a username and password to access the database, please do so now. This can be done using tools provided by your host.'
		);
		$subPartMarkers = array_merge($subPartMarkers, $driverMarkers);

		// Add step marker for main template
		$markers['step'] = t3lib_parsehtml::substituteMarkerArray(
			$formSubPart,
			$subPartMarkers,
			'###|###',
			1,
			1
		);
	}

	/**
	 * Prepares the list of database drivers for step 2.
	 *
	 * @param string $template
	 * @return string
	 */
	protected function prepareDatabaseDrivers($template) {
		$subParts = array(
			'abstractionLayer' => t3lib_parsehtml::getSubpart($template, '###ABSTRACTION_LAYER###'),
			'vendor' => t3lib_parsehtml::getSubpart($template, '###VENDOR###'),
		);

		// Create the drop-down list of available drivers
		$dropdown = '';
		foreach ($this->availableDrivers as $abstractionLayer => $drivers) {
			$options = array();
			foreach ($drivers as $driver => $label) {
				$markers = array(
					'driver' => $driver,
					'labelvendor' => $label,
					'onclick' => 'document.location=\'index.php?TYPO3_INSTALL[type]=config&mode=123&step=2&driver=' . $driver . '\';',
					'selected' => '',
				);
				if ($driver === $this->driver) {
					$markers['selected'] .= ' selected="selected"';
				}
				$options[] = t3lib_parsehtml::substituteMarkerArray(
					$subParts['vendor'],
					$markers,
					'###|###',
					1
				);
			}
			$subPart = t3lib_parsehtml::substituteSubpart(
				$subParts['abstractionLayer'],
				'###VENDOR###',
				implode("\n", $options)
			);
			$dropdown .= t3lib_parsehtml::substituteMarker(
				$subPart,
				'###LABELABSTRACTIONLAYER###',
				$abstractionLayer
			);
		}
		$form = t3lib_parsehtml::substituteSubpart(
			$template,
			'###ABSTRACTION_LAYER###',
			$dropdown
		);
		$form = t3lib_parsehtml::substituteMarker(
			$form,
			'###LABELDRIVER###',
			'Driver'
		);
		return $form;
	}

	/**
	 * Returns a list of DBAL supported database drivers, with a user-friendly name
	 * and any PHP module dependency.
	 *
	 * @return array
	 */
	protected function getSupportedDrivers() {
		$supportedDrivers = array(
			'Native' => array(
				'mysql' => array(
					'label' => 'MySQL/MySQLi (recommended)',
					'combine' => 'OR',
					'extensions' => array('mysql', 'mysqli'),
				),
				'mssql' => array(
					'label' => 'Microsoft SQL Server',
					'extensions' => array('mssql'),
				),
				'oci8' => array(
					'label' => 'Oracle OCI8',
					'extensions' => array('oci8'),
				),
				'postgres' => array(
					'label' => 'PostgreSQL',
					'extensions' => array('pgsql'),
				)
			),
			'ODBC' => array(
				'odbc_mssql' => array(
					'label' => 'Microsoft SQL Server',
					'extensions' => array('odbc', 'mssql'),
				),
			),
		);
		return $supportedDrivers;
	}

	/**
	 * Returns a list of database drivers that are available on current server.
	 *
	 * @return array
	 */
	protected function getAvailableDrivers() {
		$availableDrivers = array();
		foreach ($this->supportedDrivers as $abstractionLayer => $drivers) {
			foreach ($drivers as $driver => $info) {
				if (isset($info['combine']) && $info['combine'] === 'OR') {
					$isAvailable = FALSE;
				} else {
					$isAvailable = TRUE;
				}

				// Loop through each PHP module dependency to ensure it is loaded
				foreach ($info['extensions'] as $extension) {
					if (isset($info['combine']) && $info['combine'] === 'OR') {
						$isAvailable |= extension_loaded($extension);
					} else {
						$isAvailable &= extension_loaded($extension);
					}
				}

				if ($isAvailable) {
					if (!isset($availableDrivers[$abstractionLayer])) {
						$availableDrivers[$abstractionLayer] = array();
					}
					$availableDrivers[$abstractionLayer][$driver] = $info['label'];
				}
			}
		}
		return $availableDrivers;
	}

	/**
	 * Returns the number of available drivers.
	 *
	 * @return boolean
	 */
	protected function getNumberOfAvailableDrivers() {
		$count = 0;
		foreach ($this->availableDrivers as $drivers) {
			$count += count($drivers);
		}
		return $count;
	}

	/**
	 * Returns the driver that is selected by default in the
	 * Install Tool dropdown list.
	 *
	 * @return string
	 */
	protected function getDefaultDriver() {
		$defaultDriver = '';
		if (count($this->availableDrivers)) {
			$abstractionLayers = array_keys($this->availableDrivers);
			$drivers = array_keys($this->availableDrivers[$abstractionLayers[0]]);
			$defaultDriver = $drivers[0];
		}
		return $defaultDriver;
	}

	/**
	 * Creates a specialized form to configure the database.
	 *
	 * @param array $markers
	 * @param tx_install $instObj
	 */
	protected function createDatabaseForm(array &$markers, tx_install $instObj) {
		$error_missingConnect = '
			<p class="typo3-message message-error">
				<strong>
					There is no connection to the database!
				</strong>
				<br />
				(Username: <em>' . TYPO3_db_username . '</em>,
				Host: <em>' . TYPO3_db_host . '</em>,
				Using Password: YES)
				<br />
				Go to Step 1 and enter a valid username and password!
			</p>
		';

		// Add header marker for main template
		$markers['header'] = 'Select database';
		// There should be a database host connection at this point
		if ($result = $GLOBALS['TYPO3_DB']->sql_pconnect(
			TYPO3_db_host, TYPO3_db_username, TYPO3_db_password
		)) {
			// Get the template file
			$templateFile = @file_get_contents(
				t3lib_extMgm::extPath('dbal') . $this->templateFilePath . 'install.html'
			);
			// Get the template part from the file
			$template = t3lib_parsehtml::getSubpart(
				$templateFile, '###TEMPLATE###'
			);
			// Get the subpart for the database choice step
			$formSubPart = t3lib_parsehtml::getSubpart(
				$template, '###DATABASE_FORM###'
			);
			// Get the subpart for the database options
			$step3DatabaseOptionsSubPart = t3lib_parsehtml::getSubpart(
				$formSubPart, '###DATABASEOPTIONS###'
			);

			$dbArr = $instObj->getDatabaseList();
			$dbIncluded = FALSE;
			foreach ($dbArr as $dbname) {
				// Define the markers content for database options
				$step3DatabaseOptionMarkers = array(
					'databaseValue' => htmlspecialchars($dbname),
					'databaseSelected' => ($dbname === TYPO3_db) ? 'selected="selected"' : '',
					'databaseName' => htmlspecialchars($dbname)
				);
				// Add the option HTML to an array
				$step3DatabaseOptions[] = t3lib_parsehtml::substituteMarkerArray(
					$step3DatabaseOptionsSubPart,
					$step3DatabaseOptionMarkers,
					'###|###',
					1,
					1
				);
				if ($dbname === TYPO3_db) {
					$dbIncluded = TRUE;
				}
			}
			if (!$dbIncluded && TYPO3_db) {
				// // Define the markers content when no access
				$step3DatabaseOptionMarkers = array(
					'databaseValue' => htmlspecialchars(TYPO3_db),
					'databaseSelected' => 'selected="selected"',
					'databaseName' => htmlspecialchars(TYPO3_db) . ' (NO ACCESS!)'
				);
				// Add the option HTML to an array
				$step3DatabaseOptions[] = t3lib_parsehtml::substituteMarkerArray(
					$step3DatabaseOptionsSubPart,
					$step3DatabaseOptionMarkers,
					'###|###',
					1,
					1
				);
			}
			// Substitute the subpart for the database options
			$content = t3lib_parsehtml::substituteSubpart(
				$formSubPart,
				'###DATABASEOPTIONS###',
				implode(chr(10), $step3DatabaseOptions)
			);
			// Define the markers content
			$step3SubPartMarkers = array(
				'step' => $instObj->step + 1,
				'action' => htmlspecialchars($instObj->action),
				'llOption2' => 'Select an EMPTY existing database:',
				'llRemark2' => 'Any tables used by TYPO3 will be overwritten.',
				'continue' => 'Continue'
			);
			// Add step marker for main template
			$markers['step'] = t3lib_parsehtml::substituteMarkerArray(
				$content,
				$step3SubPartMarkers,
				'###|###',
				1,
				1
			);
		} else {
			// Add step marker for main template when no connection
			$markers['step'] = $error_missingConnect;
		}
	}

}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/dbal/class.tx_dbal_installtool.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/dbal/class.tx_dbal_installtool.php']);
}
?>