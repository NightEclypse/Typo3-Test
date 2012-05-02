<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2011 Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
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
 * @author Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
 * @package Workspaces
 * @subpackage ExtDirect
 */
class Tx_Workspaces_ExtDirect_ToolbarMenu {

	/**
	 * @param $parameter
	 * @return array
	 */
	public function toggleWorkspacePreviewMode($parameter) {
		$newState = $GLOBALS['BE_USER']->user['workspace_preview'] ? '0' : '1';
		$GLOBALS['BE_USER']->setWorkspacePreview($newState);

		return array('newWorkspacePreviewState' => $newState);
	}

	/**
	 * @param $parameter
	 * @return array
	 */
	public function setWorkspace($parameter) {
		$workspaceId = intval($parameter->workSpaceId);

		$GLOBALS['BE_USER']->setWorkspace($workspaceId);
		return array(
			'title' => Tx_Workspaces_Service_Workspaces::getWorkspaceTitle($workspaceId),
			'id' => $workspaceId
		);
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/workspaces/Classes/ExtDirect/ToolbarMenu.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/workspaces/Classes/ExtDirect/ToolbarMenu.php']);
}
?>