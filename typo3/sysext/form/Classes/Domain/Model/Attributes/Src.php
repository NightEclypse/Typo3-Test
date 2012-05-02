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
 * Attribute 'src'
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_Domain_Model_Attributes_Src extends tx_form_Domain_Model_Attributes_Abstract {
	/**
	 * Gets the attribute 'src'.
	 * Used with the element 'input'
	 * URI type definition
	 *
	 * When the type attribute has the value "image", this attribute
	 * specifies the location of the image to be used to decorate the
	 * graphical submit button.
	 *
	 * @return string Attribute value
	 * @see tslib_cObj::getImgResource()
	 */
	public function getValue() {
		$attribute = $this->localCobj->IMG_RESOURCE($this->value);

		return $attribute;
	}
}
?>