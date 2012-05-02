<?php

/*                                                                        *
 * This script is backported from the FLOW3 package "TYPO3.Fluid".        *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 *                                                                        *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 */
class Tx_Fluid_Tests_Unit_ViewHelpers_Format_CropViewHelperTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * var Tx_Fluid_ViewHelpers_Format_CropViewHelper
	 */
	protected $viewHelper;

	/**
	 * @var tslib_cObj
	 */
	protected $mockContentObject;

	/**
	 * @var Tx_Extbase_Configuration_ConfigurationManagerInterface
	 */
	protected $mockConfigurationManager;

	public function setUp() {
		parent::setUp();

		$this->mockContentObject = $this->getMock('tslib_cObj', array(), array(), '', FALSE);
		$this->mockConfigurationManager = $this->getMock('Tx_Extbase_Configuration_ConfigurationManagerInterface');
		$this->mockConfigurationManager->expects($this->any())->method('getContentObject')->will($this->returnValue($this->mockContentObject));
		$this->viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_CropViewHelper', array('renderChildren'));
		$this->viewHelper->injectConfigurationManager($this->mockConfigurationManager);
		$this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('Some Content'));
	}

	/**
	 * @test
	 */
	public function viewHelperCallsCropHtmlByDefault() {
		$this->mockContentObject->expects($this->once())->method('cropHTML')->with('Some Content', '123|...|1')->will($this->returnValue('Cropped Content'));
		$actualResult = $this->viewHelper->render(123);
		$this->assertEquals('Cropped Content', $actualResult);
	}

	/**
	 * @test
	 */
	public function viewHelperCallsCropHtmlByDefault2() {
		$this->mockContentObject->expects($this->once())->method('cropHTML')->with('Some Content', '-321|custom suffix|1')->will($this->returnValue('Cropped Content'));
		$actualResult = $this->viewHelper->render(-321, 'custom suffix');
		$this->assertEquals('Cropped Content', $actualResult);
	}

	/**
	 * @test
	 */
	public function respectWordBoundariesCanBeDisabled() {
		$this->mockContentObject->expects($this->once())->method('cropHTML')->with('Some Content', '123|...|')->will($this->returnValue('Cropped Content'));
		$actualResult = $this->viewHelper->render(123, '...', FALSE);
		$this->assertEquals('Cropped Content', $actualResult);
	}

	/**
	 * @test
	 */
	public function respectHtmlCanBeDisabled() {
		$this->mockContentObject->expects($this->once())->method('crop')->with('Some Content', '123|...|1')->will($this->returnValue('Cropped Content'));
		$actualResult = $this->viewHelper->render(123, '...', TRUE, FALSE);
		$this->assertEquals('Cropped Content', $actualResult);
	}
}
?>
