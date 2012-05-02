<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2011 TYPO3 Tree Team <http://forge.typo3.org/projects/typo3v4-extjstrees>
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
 * Abstract Context Menu Renderer
 *
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 * @package TYPO3
 * @subpackage t3lib
 */
abstract class t3lib_contextmenu_renderer_Abstract {
	/**
	 * Renders an action recursive or just a single one
	 *
	 * @param t3lib_contextmenu_Action $action
	 * @param bool $recursive
	 * @return mixed
	 */
	abstract public function renderAction(t3lib_contextmenu_Action $action, $recursive = FALSE);

	/**
	 * Renders an action collection recursive or just a single one
	 *
	 * @param t3lib_contextmenu_ActionCollection $actionCollection
	 * @param bool $recursive
	 * @return mixed
	 */
	abstract public function renderActionCollection(
		t3lib_contextmenu_ActionCollection $actionCollection, $recursive = FALSE
	);

	/**
	 * Renders a context menu recursive or just a single one
	 *
	 * @param t3lib_contextmenu_AbstractContextMenu $contextMenu
	 * @param bool $recursive
	 * @return mixed
	 */
	abstract public function renderContextMenu(
		t3lib_contextmenu_AbstractContextMenu $contextMenu, $recursive = FALSE
	);
}

?>