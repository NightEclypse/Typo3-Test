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
 * Select model object
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_Domain_Model_Element_Select extends tx_form_Domain_Model_Element_Container {

	/**
	 * Allowed attributes for this object
	 *
	 * @var array
	 */
	protected $allowedAttributes = array(
		'class' => '',
		'disabled' => '',
		'id' => '',
		'lang' => '',
		'multiple' => '',
		'name' => '',
		'size' => '',
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
	 * Add child object to this element
	 *
	 * @param tx_form_Domain_Model_Element_Option $element The child object
	 * @return tx_form_Domain_Model_Element_Select
	 */
	public function addElement(tx_form_Domain_Model_Element_Option $element) {
		$element->setParentName($this->getName());
		$this->elements[] = $element;
		return $this;
	}

	/**
	 * Set a specific attribute by name and value
	 *
	 * @param string $attribute Name of the attribute
	 * @param mixed $value Value of the attribute
	 * @return tx_form_Domain_Model_Element_Select
	 */
	public function setAttribute($attribute, $value) {
		if (array_key_exists($attribute, $this->allowedAttributes)) {
			$this->attributes->addAttribute($attribute, $value);
		}

		if (($attribute === 'name'
			&& $this->attributes->hasAttribute('multiple')
			&& $this->attributes->getValue('multiple') === 'multiple')
			|| ($attribute === 'multiple'
			&& $this->attributes->hasAttribute('name')))
		{
			/** @var $nameAttribute tx_form_Domain_Model_Attributes_Name */
			$nameAttribute = $this->attributes->getAttributeObjectByKey('name');
			$nameAttribute->setAddition('[]');
		}

		return $this;
	}
}
?>