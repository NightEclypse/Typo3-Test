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
 * File Minimum size rule
 * The file size must be bigger than the minimum
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_System_Validate_Fileminimumsize extends tx_form_System_Validate_Abstract implements tx_form_System_Validate_Interface {

	/**
	 * Minimum value
	 *
	 * @var mixed
	 */
	protected $minimum;

	/**
	 * Constructor
	 *
	 * @param array $arguments Typoscript configuration
	 * @return void
	 */
	public function __construct($arguments) {
		$this->setMinimum($arguments['minimum']);

		parent::__construct($arguments);
	}

	/**
	 * Returns TRUE if submitted value validates according to rule
	 *
	 * @return boolean
	 * @see tx_form_System_Validate_Interface::isValid()
	 */
	public function isValid() {
		if ($this->requestHandler->has($this->fieldName)) {
			$fileValue = $this->requestHandler->getByMethod($this->fieldName);
			$value = $fileValue['size'];

			if ($value < $this->minimum) {
				return FALSE;
			}
		}
		return TRUE;
	}

	/**
	 * Set the minimum value
	 *
	 * @param integer $minimum Minimum value
	 * @return object Rule object
	 */
	public function setMinimum($minimum) {
		$this->minimum = (integer) $minimum;

		return $this;
	}

	/**
	 * Substitute makers in the message text
	 * Overrides the abstract
	 *
	 * @param string $message Message text with markers
	 * @return string Message text with substituted markers
	 */
	protected function substituteValues($message) {
		$message = str_replace(
			'%minimum',
			t3lib_div::formatSize($this->minimum),
			$message
		);

		return $message;
	}
}
?>