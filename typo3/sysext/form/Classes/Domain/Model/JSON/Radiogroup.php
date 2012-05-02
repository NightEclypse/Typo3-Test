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
 * JSON radiogroup
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_Domain_Model_JSON_Radiogroup extends tx_form_Domain_Model_JSON_Fieldset {
	/**
	 * The ExtJS xtype of the element
	 *
	 * @var string
	 */
	public $xtype = 'typo3-form-wizard-elements-predefined-radiogroup';

	/**
	 * The configuration array for the xtype
	 *
	 * @var array
	 */
	public $configuration = array(
		'attributes' => array(),
		'legend' => array(
			'value' => ''
		),
		'options' => array(),
		'various' => array(
			'name' => ''
		),
		'validation' => array()
	);

	/**
	 * Allowed attributes for this object
	 *
	 * @var array
	 */
	protected $allowedAttributes = array(
		'class',
		'dir',
		'id',
		'lang',
		'style'
	);

	/**
	 * Set all the parameters for this object
	 *
	 * @param array $parameters Configuration array
	 * @return void
	 * @see tx_form_Domain_Model_Json_Fieldset::setParameters()
	 */
	public function setParameters(array $parameters) {
		parent::setParameters($parameters);
		$this->setOptions($parameters);
		$this->setVarious($parameters);
	}

	/**
	 * Set the options for this object
	 *
	 * @param array $parameters Configuration array
	 * @return void
	 */
	protected function setOptions(array $parameters) {
		if (is_array($parameters)) {
			$keys = t3lib_TStemplate::sortedKeyList($parameters);
			foreach ($keys as $key)	{
				$class = $parameters[$key];
				if (intval($key) && !strstr($key, '.')) {
					if (isset($parameters[$key . '.']) && $class === 'RADIO') {
						$childElementArguments = $parameters[$key . '.'];
						if (isset($childElementArguments['checked'])) {
							$childElementArguments['attributes']['selected'] = 'selected';
							unset($childElementArguments['checked']);
						}
						if (isset($childElementArguments['label.'])) {
							$childElementArguments['data'] = $childElementArguments['label.']['value'];
							unset($childElementArguments['label.']);
						}
						$this->configuration['options'][] = $childElementArguments;
					}
				}
			}
		}
	}

	/**
	 * Set the various properties for this object
	 *
	 * @param array $parameters Configuration array
	 * @return void
	 */
	protected function setVarious(array $parameters) {
		if (isset($parameters['name'])) {
			$this->configuration['various']['name'] = $parameters['name'];
		}
	}
}
?>