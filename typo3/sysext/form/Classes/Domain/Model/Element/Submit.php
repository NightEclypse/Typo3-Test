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
 * Submit button model object
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_Domain_Model_Element_Submit extends tx_form_Domain_Model_Element_Abstract {

	/**
	 * Allowed attributes for this object
	 *
	 * @var array
	 */
	protected $allowedAttributes = array(
		'accesskey' => '',
		'alt' => '',
		'class' => '',
		'dir' => '',
		'disabled' => '',
		'id' => '',
		'lang' => '',
		'name' => '',
		'style' => '',
		'tabindex' => '',
		'title' => '',
		'type' => 'submit',
		'value' => '',
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
	 * Set the value of the button
	 * Checks if value is set from Typoscript,
	 * otherwise use localized value.
	 * Also changes the value attribute
	 *
	 * @param string $value Value to display on button
	 * @return void
	 * @see tx_form_Domain_Model_Element::setValue()
	 */
	public function setValue($value = '') {
		/** @var $localizationHandler tx_form_System_Localization */
		$localizationHandler = t3lib_div::makeInstance('tx_form_System_Localization');

			// value not set from typoscript
		$oldValue = $this->getAttributeValue('value');
		if (empty($oldValue)) {
			if (!empty($value)) {
				$newValue = (string) $value;
			} else {
				$newValue = $localizationHandler->getLocalLanguageLabel('tx_form_domain_model_element_submit.value');
			}
			$this->value = (string) $newValue;
			$this->setAttribute('value', $newValue);
		}
	}
}
?>