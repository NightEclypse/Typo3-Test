<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2011 Ingo Renner <ingo@typo3.org>
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
 * interface for classes which hook into localRecordList and do additional getTable processing
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage t3lib
 */
interface t3lib_localRecordListGetTableHook {

	/**
	 * modifies the DB list query
	 *
	 * @param	string				the current database table
	 * @param	integer				the record's page ID
	 * @param	string				an additional WHERE clause
	 * @param	string				comma separated list of selected fields
	 * @param	localRecordList		parent localRecordList object
	 * @return	void
	 */
	public function getDBlistQuery($table, $pageId, &$additionalWhereClause, &$selectedFieldsList, &$parentObject);

}

?>