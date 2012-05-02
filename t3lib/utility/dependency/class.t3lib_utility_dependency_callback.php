<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2010-2011 Oliver Hader <oliver@typo3.org>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the textfile GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Object to hold information on a callback to a defined object and method.
 */
class t3lib_utility_Dependency_Callback {
	/**
	 * @var object
	 */
	protected $object;

	/**
	 * @var string
	 */
	protected $method;

	/**
	 * @var array
	 */
	protected $targetArguments;

	/**
	 * Creates the objects.
	 *
	 * @param object $object
	 * @param string $method
	 * @param array $targetArguments (optional)
	 */
	public function __construct($object, $method, array $targetArguments = array()) {
		$this->object = $object;
		$this->method = $method;
		$this->targetArguments = $targetArguments;
		$this->targetArguments['target'] = $object;
	}

	/**
	 * Executes the callback.
	 *
	 * @param array $callerArguments
	 * @param object $caller
	 * @param string $eventName
	 * @return mixed
	 */
	public function execute(array $callerArguments = array(), $caller, $eventName) {
		return call_user_func_array(
			array($this->object, $this->method),
			array($callerArguments, $this->targetArguments, $caller, $eventName)
		);
	}
}
?>