<?php

/*                                                                        *
 * This script is backported from the FLOW3 package "TYPO3.Fluid".        *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Deprecated. Use <f:flashMessages> instead!
 *
 * @deprecated since Extbase 1.3.0; will be removed in Extbase 1.5.0
 */
class Tx_Fluid_ViewHelpers_RenderFlashMessagesViewHelper extends Tx_Fluid_ViewHelpers_FlashMessagesViewHelper {

	/**
	 * @return void
	 */
	public function initialize() {
		t3lib_div::logDeprecatedFunction();
		return parent::initialize();
	}
}

?>
