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
 * JSON checkbox
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_Domain_Model_JSON_Checkbox extends tx_form_Domain_Model_JSON_Element {
	/**
	 * The ExtJS xtype of the element
	 *
	 * @var string
	 */
	public $xtype = 'typo3-form-wizard-elements-basic-checkbox';

	/**
	 * The configuration array for the xtype
	 *
	 * @var array
	 */
	public $configuration = array(
		'attributes' => array(
			'type' => 'checkbox'
		),
		'filters' => array(),
		'label' => array(
			'value' => ''
		),
		'layout' => 'back',
		'validation' => array()
	);

	/**
	 * Allowed attributes for this object
	 *
	 * @var array
	 */
	protected $allowedAttributes = array(
		'accesskey',
		'alt',
		'class',
		'dir',
		'disabled',
		'id',
		'lang',
		'name',
		'style: ',
		'tabindex',
		'title',
		'type',
		'value'
	);
}
?>