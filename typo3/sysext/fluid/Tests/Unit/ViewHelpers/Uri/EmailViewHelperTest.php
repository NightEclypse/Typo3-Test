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
 * Testcase for the email uri view helper
 *
 */
class Tx_Fluid_Tests_Unit_ViewHelpers_Uri_EmailViewHelperTest extends Tx_Fluid_ViewHelpers_ViewHelperBaseTestcase {

	/**
	 * var Tx_Fluid_ViewHelpers_Uri_EmailViewHelper
	 */
	protected $viewHelper;

	/**
	 * @var tslib_cObj
	 */
	protected $cObjBackup;

	public function setUp() {
		parent::setUp();

		$this->cObjBackup = $GLOBALS['TSFE']->cObj;
		$GLOBALS['TSFE']->cObj = $this->getMock('tslib_cObj', array(), array(), '', FALSE);

		$this->viewHelper = new Tx_Fluid_ViewHelpers_Uri_EmailViewHelper();
		$this->injectDependenciesIntoViewHelper($this->viewHelper);
		$this->viewHelper->initializeArguments();
	}

	public function tearDown() {
		$GLOBALS['TSFE']->cObj = $this->cObjBackup;
	}

	/**
	 * @test
	 */
	public function renderReturnsFirstResultOfGetMailTo() {
		#$GLOBALS['TSFE']->cObj->expects($this->once())->method('getMailTo')->with('some@email.tld', 'some@email.tld')->will($this->returnValue(array('mailto:some@email.tld', 'some@email.tld')));

		$this->viewHelper->initialize();
		$actualResult = $this->viewHelper->render('some@email.tld');

		$this->assertEquals('mailto:some@email.tld', $actualResult);
	}
}


?>
