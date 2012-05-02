<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2011 Tolleiv Nietsch <nietsch@aoemedia.de>
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
 * interface for classes which hook into tslib_menu
 *
 * @author	Tolleiv Nietsch <nietsch@aoemedia.de>
 * @package TYPO3
 */

interface tslib_menu_filterMenuPagesHook {

	/**
	 * Checks if a page is OK to include in the final menu item array.
	 *
	 * @param	array		Array of menu items
	 * @param	array		Array of page uids which are to be excluded
	 * @param	boolean		If set, then the page is a spacer.
	 * @param	tslib_menu	The menu object
	 * @return	boolean		Returns TRUE if the page can be safely included.
	 */
	public function processFilter (array &$data, array $banUidArray, $spacer, tslib_menu $obj);
}

?>