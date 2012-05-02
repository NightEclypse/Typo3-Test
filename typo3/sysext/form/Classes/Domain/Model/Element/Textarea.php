<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Patrick Broens (patrick@patrickbroens.nl)
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
 * Textarea model object
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_Domain_Model_Element_Textarea extends tx_form_Domain_Model_Element_Abstract {

	/**
	 * Allowed attributes for this object
	 *
	 * @var array
	 */
	protected $allowedAttributes = array(
		'accesskey' => '',
		'class' => '',
		'cols' => '40',
		'dir' => '',
		'disabled' => '',
		'id' => '',
		'lang' => '',
		'name' => '',
		'readonly' => '',
		'rows' => '5',
		'style' => '',
		'tabindex' => '',
		'title' => '',
	);

	/**
	 * Mandatory attributes for this object
	 *
	 * @var array
	 */
	protected $mandatoryAttributes = array(
		'name',
		'id'
	);

	/**
	 * Returns the content of the textarea tag
	 * <textarea>content</textarea>
	 *
	 * @return string
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * Check the request handler on input of this field,
	 * filter the submitted data and add this to the right
	 * datapart of the element
	 *
	 * @return tx_form_Domain_Model_Element_Textarea
	 * @see tx_form_Domain_Model_Element::checkFilterAndSetIncomingDataFromRequest()
	 */
	public function checkFilterAndSetIncomingDataFromRequest() {
		if ($this->requestHandler->has($this->getName())) {
			$value = $this->requestHandler->getByMethod($this->getName());

			$value = $this->filter->filter($value);

			$this->data = $value;
		}

		return $this;
	}
}
?>