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
 * Typoscript to JSON converter
 *
 * Takes the incoming Typoscript and converts it to Json
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_Domain_Factory_TyposcriptToJson {
	/**
	 * @var array
	 */
	protected $validationRules;

	/**
	 * Convert TypoScript string to JSON
	 *
	 * @param string $typoscript TypoScript string containing all configuration for the form
	 * @return string The JSON for the form
	 */
	public function convert(array $typoscript) {
		$this->setValidationRules($typoscript);
		$jsonObject = $this->createElement('form', $typoscript);

		return $jsonObject;
	}

	/**
	 * Create element by loading class
	 * and instantiating the object
	 *
	 * @param string $class Type of element
	 * @param array $arguments Configuration array
	 * @return tx_form_Domain_Model_JSON_Element
	 */
	public function createElement($class, array $arguments = array()) {
		$class = strtolower((string) $class);
		$className = 'tx_form_Domain_Model_Json_' . ucfirst($class);

		$this->addValidationRules($arguments);

		/** @var $object tx_form_Domain_Model_JSON_Element */
		$object = t3lib_div::makeInstance($className);
		$object->setParameters($arguments);

		if ($object->childElementsAllowed()) {
			$this->getChildElementsByIntegerKey($object, $arguments);
		}

		return $object;
	}

	/**
	 * Rendering of a "numerical array" of Form objects from TypoScript
	 * Creates new object for each element found
	 *
	 * @param tx_form_Domain_Model_JSON_Element $parentElement Parent model object
	 * @param array $arguments Configuration array
	 * @return void
	 */
	protected function getChildElementsByIntegerKey(tx_form_Domain_Model_JSON_Element $parentElement, array $typoscript) {
		if (is_array($typoscript)) {
			$keys = t3lib_TStemplate::sortedKeyList($typoscript);
			foreach ($keys as $key)	{
				$class = $typoscript[$key];
				if (intval($key) && !strstr($key, '.')) {
					if (isset($typoscript[$key . '.'])) {
						$elementArguments = $typoscript[$key . '.'];
					} else {
						$elementArguments = array();
					}
					$this->setElementType($parentElement, $class, $elementArguments);
				}
			}
		}
	}

	/**
	 * Set the element type of the object
	 *
	 * Checks if the typoscript object is part of the FORM or has a predefined
	 * class for name or header object
	 *
	 * @param tx_form_Domain_Model_JSON_Element $parentElement The parent object
	 * @param string $class A predefined class
	 * @param array $arguments Configuration array
	 * @return void
	 */
	private function setElementType(tx_form_Domain_Model_JSON_Element $parentElement, $class, array $arguments) {
		if (in_array($class, tx_form_Common::getInstance()->getFormObjects())) {
			if (strstr($arguments['class'], 'predefined-name')) {
				$class = 'NAME';
			}
			$this->addElement($parentElement, $class, $arguments);
		}
	}

	/**
	 * Add child object to this element
	 *
	 * @param tx_form_Domain_Model_JSON_Element $parentElement The parent object
	 * @param string $class Type of element
	 * @param array $arguments Configuration array
	 * @return void
	 */
	public function addElement(tx_form_Domain_Model_JSON_Element $parentElement, $class, array $arguments) {
		$element = $this->createElement($class, $arguments);
		$parentElement->addElement($element);
	}

	/**
	 * Set the validation rules
	 *
	 * @param array $typoscript Configuration array
	 * @return void
	 */
	protected function setValidationRules(array $typoscript) {
		if (isset($typoscript['rules.']) && is_array($typoscript['rules.'])) {
			$this->validationRules = $typoscript['rules.'];
		}
	}

	/**
	 * Add validation rules to an element if available
	 *
	 * In TypoScript the validation rules belong to the form and are connected
	 * to the elements by name. However, in the wizard, they are added to the
	 * element for usability
	 *
	 * @param array $arguments The element arguments
	 * @return void
	 */
	protected function addValidationRules(array &$arguments) {
		$validationRulesAvailable = FALSE;

		if (!empty($this->validationRules) && isset($arguments['name'])) {
			foreach ($this->validationRules as $key => $ruleName) {
				if (intval($key) && !strstr($key, '.')) {
					$ruleConfiguration = array();
					if (isset($this->validationRules[$key . '.'])) {
						$ruleConfiguration = $this->validationRules[$key . '.'];
						if (isset($ruleConfiguration['element']) && $ruleConfiguration['element'] === $arguments['name']) {
							$arguments['validation'][$ruleName] = $ruleConfiguration;
						}
					}
				}
			}
		}
	}
}
?>