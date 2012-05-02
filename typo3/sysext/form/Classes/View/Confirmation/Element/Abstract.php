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
 * Abstract class for the form elements view
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
abstract class tx_form_View_Confirmation_Element_Abstract {

	/**
	 * The model for the current object
	 *
	 * @var tx_form_Domain_Model_Element_Abstract
	 */
	protected $model;

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
	 * @param object $model Current elements model
	 * @return void
	 */
	public function __construct($model) {
		$this->model = $model;
	}

	/**
	 * Parse the XML of a view object,
	 * check the node type and name
	 * and add the proper XML part of child tags
	 * to the DOMDocument of the current tag
	 *
	 * @param DOMDocument $dom
	 * @param DOMNode $reference Current XML structure
	 * @param boolean $emptyElement
	 * @return boolean
	 */
	protected function parseXML(DOMDocument $dom, DOMNode $reference, $emptyElement = FALSE) {
		$node = $reference->firstChild;

		while (!is_null($node)) {
			$deleteNode = FALSE;
			$nodeType = $node->nodeType;
			$nodeName = $node->nodeName;
			switch ($nodeType) {
				case XML_TEXT_NODE:
					break;
				// @todo Consider putting these processings to the accordant Element model classes!!!
				case XML_ELEMENT_NODE:
					switch($nodeName) {
						case 'containerWrap':
							$containerWrap = $this->render('containerWrap');
							if ($containerWrap) {
								$this->replaceNodeWithFragment($dom, $node, $containerWrap);
							} else {
								$emptyElement = TRUE;
							}
							$deleteNode = TRUE;
							break;
						case 'elements':
							$replaceNode = $this->getChildElements($dom);
							if ($replaceNode) {
								$node->parentNode->replaceChild($replaceNode, $node);
							} else {
								$emptyElement = TRUE;
							}
							break;
						case 'label':
							if (!strstr(get_class($this), '_Additional_')) {
								if ($this->model->additionalIsSet($nodeName)) {
									$this->replaceNodeWithFragment($dom, $node, $this->getAdditional('label'));
								}
								$deleteNode = TRUE;
							} else {
								if (!$this->model->additionalIsSet($nodeName)) {
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
						case 'inputvalue':
							if (array_key_exists('checked', $this->model->getAllowedAttributes())) {
								if (!$this->model->hasAttribute('checked')) {
									$emptyElement = TRUE;
								}
							} elseif (
								array_key_exists('selected', $this->model->getAllowedAttributes()) &&
								!$this->model->hasAttribute('selected')
							) {
								$emptyElement = TRUE;
							} else {
								$inputValue = $this->getInputValue();
								if ($inputValue != '') {
									$replaceNode = $dom->createTextNode($this->getInputValue());
									$node->parentNode->insertBefore($replaceNode, $node);
								} else {
									$emptyElement = TRUE;
								}
							}
							$deleteNode = TRUE;
							break;
						case 'labelvalue':
						case 'legendvalue':
							$replaceNode = $dom->createTextNode($this->getAdditionalValue());
							$node->parentNode->insertBefore($replaceNode, $node);
							$deleteNode = TRUE;
							break;
					}
					break;
			}

				// Parse the child nodes of this node if available
			if ($node->hasChildNodes()) {
				$emptyElement = $this->parseXML($dom, $node, $emptyElement);
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

		return $emptyElement;
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

		$emptyElement = $this->parseXML($dom, $dom);

		if ($emptyElement) {
			return NULL;
		} elseif ($returnFirstChild) {
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
				$layout = $layoutHandler->getLayoutByObject(
					tx_form_Common::getInstance()->getLastPartOfClassName($this, TRUE),
					$layoutDefault
				);
				break;
			case 'elementWrap':
				$layoutDefault = $this->elementWrap;
				$elementWrap = $layoutHandler->getLayoutByObject(
					$type,
					$layoutDefault
				);
				$layout = str_replace('<element />', $this->getLayout('element'), $elementWrap);
				break;
			case 'containerWrap':
				$layoutDefault = $this->containerWrap;
				$layout = $layoutHandler->getLayoutByObject(
					$type,
					$layoutDefault
				);
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
				$value = $attribute->getValue();
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
		$attribute = $this->model->getAttributeValue((string) $key);

		if (!empty($attribute)) {
			$domElement->setAttribute($key, $attribute);
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
		$attribute = $this->model->getAttributeValue((string) $other);

		if (!empty($attribute)) {
			$domElement->setAttribute($key, $attribute);
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
		$className = 'tx_form_View_Confirmation_Additional_' . ucfirst($class);

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
	 * @return string
	 */
	public function getInputValue() {
		if (method_exists($this->model, 'getData')) {
			$inputValue = nl2br($this->model->getData(), TRUE);
		} else {
			$inputValue = $this->model->getAttributeValue('value');
		}

		return $inputValue;
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
			tx_form_Common::getInstance()->getLastPartOfClassName($this->model)
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