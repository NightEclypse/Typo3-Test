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

require_once(dirname(__FILE__) . '/../ViewHelperBaseTestcase.php');

/**
 * Testcase for security.ifAuthenticated view helper
 *
 */
class Tx_Fluid_Tests_Unit_ViewHelpers_Security_IfAuthenticatedViewHelperTest extends Tx_Fluid_ViewHelpers_ViewHelperBaseTestcase {

	/**
	 * var Tx_Fluid_ViewHelpers_Security_IfAuthenticatedViewHelper
	 */
	protected $viewHelper;

	/**
	 * @var tslib_fe
	 */
	protected $tsfeBackup;

	public function setUp() {
		parent::setUp();

		$this->tsfeBackup = isset($GLOBALS['TSFE']) ? $GLOBALS['TSFE'] : NULL;
		$GLOBALS['TSFE'] = new stdClass();
		$this->viewHelper = $this->getAccessibleMock('Tx_Fluid_ViewHelpers_Security_IfAuthenticatedViewHelper', array('renderThenChild', 'renderElseChild'));
		$this->viewHelper->expects($this->any())->method('renderThenChild')->will($this->returnValue('then child'));
		$this->viewHelper->expects($this->any())->method('renderElseChild')->will($this->returnValue("else child"));
		$this->injectDependenciesIntoViewHelper($this->viewHelper);
		$this->viewHelper->initializeArguments();
	}

	public function tearDown() {
		$GLOBALS['TSFE'] = $this->tsfeBackup;
	}

	/**
	 * @test
	 */
	public function viewHelperRendersThenChildIfFeUserIsLoggedIn() {
		$GLOBALS['TSFE']->loginUser = 1;

		$actualResult = $this->viewHelper->render();
		$this->assertEquals('then child', $actualResult);
	}


	/**
	 * @test
	 */
	public function viewHelperRendersElseChildIfFeUserIsNotLoggedIn() {
		$GLOBALS['TSFE']->loginUser = 0;

		$actualResult = $this->viewHelper->render();
		$this->assertEquals('else child', $actualResult);
	}
}

?>
