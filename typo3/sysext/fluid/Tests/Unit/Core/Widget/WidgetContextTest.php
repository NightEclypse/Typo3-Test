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
 * Testcase for WidgetContext
 *
 */
class Tx_Fluid_Tests_Unit_Core_Widget_WidgetContextTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @var Tx_Fluid_Core_Widget_WidgetContext
	 */
	protected $widgetContext;

	/**
	 */
	public function setUp() {
		$this->widgetContext = new Tx_Fluid_Core_Widget_WidgetContext();
	}

	/**
	 * @test
	 */
	public function widgetIdentifierCanBeReadAgain() {
		$this->widgetContext->setWidgetIdentifier('myWidgetIdentifier');
		$this->assertEquals('myWidgetIdentifier', $this->widgetContext->getWidgetIdentifier());
	}

	/**
	 * @test
	 */
	public function ajaxWidgetIdentifierCanBeReadAgain() {
		$this->widgetContext->setAjaxWidgetIdentifier(42);
		$this->assertEquals(42, $this->widgetContext->getAjaxWidgetIdentifier());
	}

	/**
	 * @test
	 */
	public function widgetConfigurationCanBeReadAgain() {
		$this->widgetContext->setWidgetConfiguration(array('key' => 'value'));
		$this->assertEquals(array('key' => 'value'), $this->widgetContext->getWidgetConfiguration());
	}

	/**
	 * @test
	 */
	public function controllerObjectNameCanBeReadAgain() {
		$this->widgetContext->setControllerObjectName('Tx_Fluid_Object_Name');
		$this->assertEquals('Tx_Fluid_Object_Name', $this->widgetContext->getControllerObjectName());
	}

	/**
	 * @test
	 */
	public function viewHelperChildNodesCanBeReadAgain() {
		$viewHelperChildNodes = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_RootNode');
		$renderingContext = $this->getMock('Tx_Fluid_Core_Rendering_RenderingContextInterface');

		$this->widgetContext->setViewHelperChildNodes($viewHelperChildNodes, $renderingContext);
		$this->assertSame($viewHelperChildNodes, $this->widgetContext->getViewHelperChildNodes());
		$this->assertSame($renderingContext, $this->widgetContext->getViewHelperChildNodeRenderingContext());
	}
}
?>