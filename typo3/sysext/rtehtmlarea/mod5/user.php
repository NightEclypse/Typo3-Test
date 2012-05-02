<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2011 Kasper Skårhøj (kasper@typo3.com)
*  (c) 2005-2011 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * User defined content for htmlArea RTE
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
 * @author	Stanislas Rolland <typo3(arobas)sjbr.ca>
 */

error_reporting (E_ALL ^ E_NOTICE);
unset($MCONF);
require('conf.php');
require($BACK_PATH.'init.php');
require($BACK_PATH.'template.php');
$LANG->includeLLFile('EXT:rtehtmlarea/mod5/locallang.xml');
$LANG->includeLLFile('EXT:rtehtmlarea/htmlarea/locallang_dialogs.xml');

// Make instance:
$SOBE = t3lib_div::makeInstance('tx_rtehtmlarea_user');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

?>