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
 * Abstract class for the form elements view
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
abstract class tx_form_View_Form_Element_Abstract {

	/**
	 * The model for the current object
	 *
	 * @var tx_form_Domain_Model_Element_Abstract
	 */
	protected $model;

	/**
	 * @var string
	 */
	protected $expectedModelName;

	/**
	 * Wrap for elements
	 *
	 * @var string
	 */
	protected $elementWrap = '
		<li>
			<element />
		</li>
	';

	/**
	 * True if element needs no element wrap
	 * like <li>element</li>
	 *
	 * @var boolean
	 */
	protected $noWrap = FALSE;

	/**
	 * Constructor
	 *
	 * @param tx_form_Domain_Model_Element_Abstract $model Current elements model
	 * @return void
	 */
	public function __construct(tx_form_Domain_Model_Element_Abstract $model) {
		if ($this->isValidModel($model) === FALSE) {
			throw new RuntimeException('Unexpected model "' . get_class($model) . '".');
		}

		$this->model = $model;
	}

	/**
	 * Determines whether the model is expected in this object.
	 *
	 * @param tx_form_Domain_Model_Element_Abstract $model
	 * @return boolean
	 */
	protected function isValidModel(tx_form_Domain_Model_Element_Abstract $model) {
		return is_a($model, $this->getExpectedModelName($model));
	}

	/**
	 * Gets the expected model name.
	 *
	 * @param tx_form_Domain_Model_Element_Abstract $model
	 * @return string
	 */
	protected function getExpectedModelName(tx_form_Domain_Model_Element_Abstract $model) {
		if (!isset($this->expectedModelName)) {
			$specificName = tx_form_Common::getInstance()->getLastPartOfClassName($this);
			$this->expectedModelName = 'tx_form_Domain_Model_Element_' . $specificName;
		}

		return $this->expectedModelName;
	}

	/**
	 * Parse the XML of a view object,
	 * check the node type and name
	 * and add the proper XML part of child tags
	 * to the DOMDocument of the current tag
	 *
	 * @param DOMDocument $dom
	 * @param DOMNode $reference Current XML structure
	 * @return void
	 */
	protected function parseXML(DOMDocument $dom, DOMNode $reference) {
		$node = $reference->firstChild;

		while (!is_null($node)) {
			$deleteNode = FALSE;
			$nodeType = $node->nodeType;
			$nodeName = $node->nodeName;
			switch ($nodeType) {
				case XML_TEXT_NODE:
					break;
				case XML_ELEMENT_NODE:
					switch($nodeName) {
						case 'containerWrap':
							$this->replaceNodeWithFragment($dom, $node, $this->render('containerWrap'));
							$deleteNode = TRUE;
							break;
						case 'elements':
							$replaceNode = $this->getChildElements($dom);
							$node->parentNode->replaceChild($replaceNode, $node);
							break;
						case 'button':
						case 'fieldset':
						case 'form':
						case 'input':
						case 'optgroup':
						case 'select':
							$this->setAttributes($node);
							break;
						case 'label':
							if (!strstr(get_class($this), '_Additional_')) {
								if ($this->model->additionalIsSet($nodeName)) {
									$this->replaceNodeWithFragment($dom, $node, $this->getAdditional('label'));
								}
								$deleteNode = TRUE;
							} else {
								if ($this->model->additionalIsSet($nodeName)) {
									$this->setAttributeWithValueofOtherAttribute($node, 'for', 'id');
								} else {
									$deleteNode = TRUE;
								}
							}
							break;
						case 'legend':
							if (!strstr(get_class($this), '_Additional_')) {
								if ($this->model->additionalIsSet($nodeName)) {
									$this->replaceNodeWithFragment($dom, $node, $this->getAdditional('legend'));
								}
								$deleteNode = TRUE;
							}
							break;
						case 'textarea':
						case 'option':
							$this->setAttributes($node);
							$appendNode = $dom->createTextNode($this->getElementData());
							$node->appendChild($appendNode);
							break;
						case 'errorvalue':
						case 'labelvalue':
						case 'legendvalue':
						case 'mandatoryvalue':
							$replaceNode = $dom->createTextNode($this->getAdditionalValue());
							$node->parentNode->insertBefore($replaceNode, $node);
							$deleteNode = TRUE;
							break;
						case 'mandatory':
						case 'error':
							if ($this->model->additionalIsSet($nodeName)) {
								$this->replaceNodeWithFragment($dom, $node, $this->getAdditional($nodeName));
							}
							$deleteNode = TRUE;
							break;
						case 'content':
						case 'header':
						case 'textblock':
							$replaceNode = $dom->createTextNode($this->getElementData(FALSE));
							$node->parentNode->insertBefore($replaceNode, $node);
							$deleteNode = TRUE;
							break;
					}
					break;
			}

				// Parse the child nodes of this node if available
			if ($node->hasChildNodes()) {
				$this->parseXML($dom, $node);
			}

				// Get the current node for deletion if replaced. We need this because nextSibling can be empty
			$oldNode = $node;

				// Go to next sibling to parse
			$node = $node->nextSibling;

				// Delete the old node. This can only be done after going to the next sibling
			if ($deleteNode) {
				$oldNode->parentNode->removeChild($oldNode);
			}
		}
	}

	/**
	 * Get the content for the current object as DOMDocument
	 *
	 * @param string $type Type of element for layout
	 * @param boolean $returnFirstChild If TRUE, the first child will be returned instead of the DOMDocument
	 * @return mixed DOMDocument/DOMNode XML part of the view object
	 */
	public function render($type = 'element', $returnFirstChild = TRUE) {
		$useLayout = $this->getLayout((string) $type);

		$dom = new DOMDocument('1.0', 'utf-8');
		$dom->formatOutput = TRUE;
		$dom->preserveWhiteSpace = FALSE;
		$dom->loadXML($useLayout);

		$this->parseXML($dom, $dom);

		if ($returnFirstChild) {
			return $dom->firstChild;
		} else {
			return $dom;
		}
	}

	/**
	 * Ask the layoutHandler to get the layout for this object
	 *
	 * @param string $type Layout type
	 * @return string HTML string of the layout to use for this element
	 */
	public function getLayout($type) {
		/** @var $layoutHandler tx_form_System_Layout */
		$layoutHandler = t3lib_div::makeInstance('tx_form_System_Layout');

		switch($type) {
			case 'element':
				$layoutDefault = $this->layout;
				$objectClass = get_class($this);
				$type = tx_form_Common::getInstance()->getLastPartOfClassName($this, TRUE);

				if (strstr($objectClass, '_Additional_')) {
					$additionalModel = $this->model->getAdditionalObjectByKey($type);
					$layoutOverride = $additionalModel->getLayout();
				} else {
					$layoutOverride = $this->model->getLayout();
				}

				$layout = $layoutHandler->getLayoutByObject($type, $layoutDefault, $layoutOverride);
				break;
			case 'elementWrap':
				$layoutDefault = $this->elementWrap;
				$elementWrap = $layoutHandler->getLayoutByObject($type, $layoutDefault, $layoutOverride);

				$layout = str_replace('<element />', $this->getLayout('element'), $elementWrap);
				break;
			case 'containerWrap':
				$layoutDefault = $this->containerWrap;
				$layout = $layoutHandler->getLayoutByObject($type, $layoutDefault, $layoutOverride);
				break;
		}

		return $layout;
	}

	/**
	 * Replace the current node with a document fragment
	 *
	 * @param DOMDocument $dom
	 * @param DOMNode $node Current Node
	 * @param DOMNode $value Value to import
	 * @return void
	 */
	public function replaceNodeWithFragment(DOMDocument $dom, DOMNode $node, DOMNode $value) {
		$replaceNode = $dom->createDocumentFragment();
		$domNode = $dom->importNode($value, TRUE);
		$replaceNode->appendChild($domNode);
		$node->parentNode->insertBefore($replaceNode, $node);
	}

	/**
	 * Set the attributes on the html tags according to the attributes that are
	 * assigned in the model for a certain element
	 *
	 * @param DOMElement $domElement DOM element of the specific HTML tag
	 * @return void
	 */
	public function setAttributes(DOMElement $domElement) {
		$attributes = $this->model->getAttributes();
		foreach ($attributes as $key => $attribute) {
			if (!empty($attribute)) {
				$value = htmlspecialchars($attribute->getValue(), ENT_QUOTES);
				if (!empty($value)) {
					$domElement->setAttribute($key, $value);
				}
			}
		}
	}

	/**
	 * Set a single attribute of a HTML tag specified by key
	 *
	 * @param DOMElement $domElement DOM element of the specific HTML tag
	 * @param string $key Attribute key
	 * @return void
	 */
	public function setAttribute(DOMElement $domElement, $key) {
		$value = htmlspecialchars($this->model->getAttributeValue((string) $key), ENT_QUOTES);

		if (!empty($value)) {
			$domElement->setAttribute($key, $value);
		}
	}

	/**
	 * Sets the value of an attribute with the value of another attribute,
	 * for instance equalizing the name and id attributes for the form tag
	 *
	 * @param DOMElement $domElement DOM element of the specific HTML tag
	 * @param string $key Key of the attribute which needs to be changed
	 * @param string $other Key of the attribute to take the value from
	 * @return unknown_type
	 */
	public function setAttributeWithValueofOtherAttribute(DOMElement $domElement, $key, $other) {
		$value = htmlspecialchars($this->model->getAttributeValue((string) $other), ENT_QUOTES);

		if (!empty($value)) {
			$domElement->setAttribute($key, $value);
		}
	}

	/**
	 * Load and instantiate an additional object
	 *
	 * @param string $class Type of additional
	 * @return object
	 */
	protected function createAdditional($class) {
		$class = strtolower((string) $class);
		$className = 'tx_form_View_Form_Additional_' . ucfirst($class);

		return t3lib_div::makeInstance($className, $this->model);
	}

	/**
	 * Create additional object by key and render the content
	 *
	 * @param string $key Type of additional
	 * @return DOMNode
	 */
	public function getAdditional($key) {
		$additional = $this->createAdditional($key);
		return $additional->render();
	}

	/**
	 * Get the content of tags
	 * like <option>content</option>
	 * or <textarea>content</textarea>
	 *
	 * @param boolean $encodeSpecialCharacters Whether to encode the data
	 * @return string
	 */
	public function getElementData($encodeSpecialCharacters = TRUE) {
		$elementData = $this->model->getData();

		if ($encodeSpecialCharacters) {
			$elementData = htmlspecialchars($elementData, ENT_QUOTES);
		}

		return $elementData;
	}

	/**
	 * Return the id for the element wraps,
	 * like <li id="csc-form-"> ... </li>
	 *
	 * @return string
	 */
	public function getElementWrapId() {
		$elementId = (integer) $this->model->getElementId();
		$wrapId = 'csc-form-' . $elementId;

		return $wrapId;
	}

	/**
	 * Returns the type for the element wraps,
	 * like <li class="csc-form-element csc-form-element-abstract">...</li>
	 *
	 * @return string
	 */
	public function getElementWrapType() {
		$elementType = strtolower(
			tx_form_Common::getInstance()->getLastPartOfClassName($this)
		);
		$wrapType = 'csc-form-element csc-form-element-' . $elementType;

		return $wrapType;
	}

	/**
	 * Returns all element wraps.
	 *
	 * @return string
	 */
	public function getElementWraps() {
		$wraps = array(
			$this->getElementWrapId(),
			$this->getElementWrapType(),
		);

		return implode(' ', $wraps);
	}

	/**
	 * Read the noWrap value of an element
	 * if TRUE the element does not need a element wrap
	 * like <li>element</li>
	 *
	 * @return boolean
	 */
	public function noWrap() {
		return $this->noWrap;
	}
}
?>