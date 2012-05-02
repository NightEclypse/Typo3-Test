<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

	// TYPO3 4.5 - Check the database to be utf-8 compliant
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['charsetDefaults'] = 'tx_coreupdates_charsetdefaults';

$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['changeCompatibilityVersion'] = 'tx_coreupdates_compatversion';

	// manage split includes of css_styled_contents since TYPO3 4.3
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['splitCscToMultipleTemplates'] = 'tx_coreupdates_cscsplit';

	// remove pagetype "not in menu" since TYPO3 4.2
	// as there is an option in every pagetype
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['removeNotInMenuDoktypeConversion'] = 'tx_coreupdates_notinmenu';

	// remove pagetype "advanced" since TYPO3 4.2
	// this is merged with doctype "standard" with tab view to edit
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['mergeAdvancedDoktypeConversion'] = 'tx_coreupdates_mergeadvanced';

	// add outsourced system extensions since TYPO3 4.3
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['installSystemExtensions'] = 'tx_coreupdates_installsysexts';

	// new system extensions since TYPO3 4.3
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['installNewSystemExtensions'] = 'tx_coreupdates_installnewsysexts';

	// change tt_content.imagecols=0 to 1 for proper display in TCEforms since TYPO3 4.3
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['changeImagecolsValue'] = 'tx_coreupdates_imagecols';

	// register eID script for install tool AJAX calls
$TYPO3_CONF_VARS['FE']['eID_include']['tx_install_ajax'] = 'EXT:install/mod/class.tx_install_ajax.php';

	// add static_template if needed (since TYPO3 4.4 this table is not standard)
	// if needed, sysext statictables is loaded, which gives back functionality
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['checkForStaticTypoScriptTemplates'] = 'tx_coreupdates_statictemplates';

	// warn for t3skin installed in Version 4.4
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['checkForT3SkinInstalled'] = 'tx_coreupdates_t3skin';

	// Version 4.4: warn for set CompressionLevel and warn user to update his .htaccess
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['checkForCompressionLevel'] = 'tx_coreupdates_compressionlevel';

	// Version 4.5: migrate workspaces to use custom stages and install the required extensions
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['migrateWorkspaces'] = 'tx_coreupdates_migrateworkspaces';

	// Version 4.5: Removes the ".gif" suffix from entries in sys_language
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['flagsFromSprites'] = 'tx_coreupdates_flagsfromsprite';

	// Version 4.5: Adds excludeable FlexForm fields to Backend group access lists (ACL)
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['addFlexformsToAcl'] = 'tx_coreupdates_addflexformstoacl';

	// Version 4.5: Split tt_content image_link to newline by comma
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['imagelink'] = 'tx_coreupdates_imagelink';

	// Version 4.7: Load fluid and extbase if extensions with new dependency to them are installed
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['installFluidExtbase'] = 'tx_coreupdates_installFluidExtbase';

	// Version 4.7: Migrate the flexforms of MediaElement
$TYPO3_CONF_VARS['SC_OPTIONS']['ext/install']['update']['mediaElementFlexform'] = 'tx_coreupdates_mediaFlexform';
?>