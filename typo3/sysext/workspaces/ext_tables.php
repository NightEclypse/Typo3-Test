<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}
	// avoid that this block is loaded in the frontend or within the upgrade-wizards
if (TYPO3_MODE == 'BE' && !(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_INSTALL)) {
	/**
	* Registers a Backend Module
	*/
	Tx_Extbase_Utility_Extension::registerModule(
		$_EXTKEY,
		'web',	// Make module a submodule of 'web'
		'workspaces',	// Submodule key
		'before:info', // Position
		array(
				// An array holding the controller-action-combinations that are accessible
			'Review'		=> 'index,fullIndex,singleIndex',
			'Preview'		=> 'index,newPage'
		),
		array(
			'access' => 'user,group',
			'icon'   => 'EXT:workspaces/Resources/Public/Images/moduleicon.gif',
			'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod.xml',
			'navigationComponentId' => 'typo3-pagetree',
		)
	);

		// register ExtDirect
	t3lib_extMgm::registerExtDirectComponent(
		'TYPO3.Workspaces.ExtDirect',
		t3lib_extMgm::extPath($_EXTKEY) . 'Classes/ExtDirect/Server.php:Tx_Workspaces_ExtDirect_Server',
		'web_WorkspacesWorkspaces',
		'user,group'
	);

	t3lib_extMgm::registerExtDirectComponent(
		'TYPO3.Workspaces.ExtDirectActions',
		 t3lib_extMgm::extPath($_EXTKEY) . 'Classes/ExtDirect/ActionHandler.php:Tx_Workspaces_ExtDirect_ActionHandler',
		'web_WorkspacesWorkspaces',
		'user,group'
	);

	t3lib_extMgm::registerExtDirectComponent(
		'TYPO3.Workspaces.ExtDirectMassActions',
		t3lib_extMgm::extPath($_EXTKEY) . 'Classes/ExtDirect/MassActionHandler.php:Tx_Workspaces_ExtDirect_MassActionHandler',
		'web_WorkspacesWorkspaces',
		'user,group'
	);

	t3lib_extMgm::registerExtDirectComponent(
		'TYPO3.Ajax.ExtDirect.ToolbarMenu',
		t3lib_extMgm::extPath($_EXTKEY) . 'Classes/ExtDirect/ToolbarMenu.php:Tx_Workspaces_ExtDirect_ToolbarMenu'
	);
}

/**
 * Table "sys_workspace":
 */
$TCA['sys_workspace'] = array(
	'ctrl' => array(
		'label' => 'title',
		'tstamp' => 'tstamp',
		'title' => 'LLL:EXT:lang/locallang_tca.xml:sys_workspace',
		'adminOnly' => 1,
		'rootLevel' => 1,
		'delete' => 'deleted',
		'iconfile' => 'sys_workspace.png',
		'typeicon_classes' => array(
			'default' => 'mimetypes-x-sys_workspace'
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY) . 'tca.php',
		'versioningWS_alwaysAllowLiveEdit' => TRUE,
		'dividers2tabs' => TRUE
	)
);

/**
 * Table "sys_workspace_stage":
 * Defines single custom stages which are related to sys_workspace table to create complex working processes
 * This is only the 'header' part (ctrl). The full configuration is found in t3lib/stddb/tbl_be.php
 */
$TCA['sys_workspace_stage'] = array(
	'ctrl' => array(
		'label' => 'title',
		'tstamp' => 'tstamp',
		'sortby' => 'sorting',
		'title' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xml:sys_workspace_stage',
		'adminOnly' => 1,
		'rootLevel' => 1,
		'hideTable' => TRUE,
		'delete' => 'deleted',
		'iconfile' => 'sys_workspace.png',
		'typeicon_classes' => array(
			'default' => 'mimetypes-x-sys_workspace'
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY) . 'tca.php',
		'versioningWS_alwaysAllowLiveEdit' => TRUE,
		'dividers2tabs' => TRUE
	)
);
	// todo move icons to Core sprite or keep them here and remove the todo note ;)
$icons = array(
	'sendtonextstage' => t3lib_extMgm::extRelPath($_EXTKEY) . 'Resources/Public/Images/version-workspace-sendtonextstage.png',
	'sendtoprevstage' => t3lib_extMgm::extRelPath($_EXTKEY) . 'Resources/Public/Images/version-workspace-sendtoprevstage.png',
	'generatepreviewlink' => t3lib_extMgm::extRelPath($_EXTKEY) . 'Resources/Public/Images/generate-ws-preview-link.png',
);
t3lib_SpriteManager::addSingleIcons($icons, $_EXTKEY);
t3lib_extMgm::addLLrefForTCAdescr('sys_workspace_stage','EXT:workspaces/Resources/Private/Language/locallang_csh_sysws_stage.xml');


?>
