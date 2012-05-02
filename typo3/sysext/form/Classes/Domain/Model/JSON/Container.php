<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Patrick Broens (patrick@patrickbroens.nl)
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
 * JSON container abstract
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_Domain_Model_JSON_Container extends tx_form_Domain_Model_JSON_Element {
	/**
	 * The items within this container
	 *
	 * @var array
	 */
	public $elementContainer = array(
		'hasDragAndDrop' => TRUE,
		'items' => array()
	);

	/**
	 * Add an element to this container
	 *
	 * @param tx_form_Domain_Model_JSON_Element $element The element to add
	 * @return void
	 */
	public function addElement(tx_form_Domain_Model_JSON_Element $element) {
		$this->elementContainer['items'][] = $element;
	}
}
?>