<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2011 Stanislas Rolland <typo3@sjbr.ca>
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Hook into the backend module "Reports" checking whether there are extensions installed that conflicting with htmlArea RTE
 */
class tx_rtehtmlarea_statusReport_conflictsCheck implements tx_reports_StatusProvider {
	/**
	 * Compiles a collection of system status checks as a status report.
	 *
	 * @see typo3/sysext/reports/interfaces/tx_reports_StatusProvider::getStatus()
	 */
	public function getStatus() {
		$reports = array(
			'noConflictingExtensionISInstalled' => $this->checkIfNoConflictingExtensionIsInstalled()
		);
		return $reports;
	}
	/**
	 * Check whether any conflicting extension has been installed
	 *
	 * @return	tx_reports_reports_status_Status
	 */
	protected function checkIfNoConflictingExtensionIsInstalled() {
		$title = $GLOBALS['LANG']->sL('LLL:EXT:rtehtmlarea/hooks/statusreport/locallang.xml:title');
		$conflictingExtensions = array();
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['conflicts'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['conflicts'] as $extensionKey => $version) {
				if (t3lib_extMgm::isLoaded($extensionKey)) {
					$conflictingExtensions[] = $extensionKey;
				}
			}
		}
		if (count($conflictingExtensions)) {
			$value = $GLOBALS['LANG']->sL('LLL:EXT:rtehtmlarea/hooks/statusreport/locallang.xml:keys') . ' ' . implode(', ', $conflictingExtensions);
			$message = $GLOBALS['LANG']->sL('LLL:EXT:rtehtmlarea/hooks/statusreport/locallang.xml:uninstall');
			$status = tx_reports_reports_status_Status::ERROR;
		} else {
			$value = $GLOBALS['LANG']->sL('LLL:EXT:rtehtmlarea/hooks/statusreport/locallang.xml:none');
			$message = '';
			$status = tx_reports_reports_status_Status::OK;
		}
		return t3lib_div::makeInstance('tx_reports_reports_status_Status',
			$title,
			$value,
			$message,
			$status
		);
	}
}
if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/hooks/statusreport/class.tx_rtehtmlarea_statusreport_conflictscheck.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/hooks/statusreport/class.tx_rtehtmlarea_statusreport_conflictscheck.php']);
}
?>