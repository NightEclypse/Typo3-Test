<?php

/*                                                                        *
 * This script is backported from the FLOW3 package "TYPO3.Fluid".        *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */


/**
 * Abstract Form View Helper. Bundles functionality related to direct property access of objects in other Form ViewHelpers.
 *
 * If you set the "property" attribute to the name of the property to resolve from the object, this class will
 * automatically set the name and value of a form element.
 *
 */
abstract class Tx_Fluid_ViewHelpers_Form_AbstractFormViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractTagBasedViewHelper {

	/**
	 * @var Tx_Extbase_Persistence_ManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * Injects the FLOW3 Persistence Manager
	 *
	 * @param Tx_Extbase_Persistence_ManagerInterface $persistenceManager
	 * @return void
	 */
	public function injectPersistenceManager(Tx_Extbase_Persistence_ManagerInterface $persistenceManager) {
		$this->persistenceManager = $persistenceManager;
	}

	/**
	 * Prefixes / namespaces the given name with the form field prefix
	 *
	 * @param string $fieldName field name to be prefixed
	 * @return string namespaced field name
	 */
	protected function prefixFieldName($fieldName) {
		if ($fieldName === NULL || $fieldName === '') {
			return '';
		}
		if (!$this->viewHelperVariableContainer->exists('Tx_Fluid_ViewHelpers_FormViewHelper', 'fieldNamePrefix')) {
			return $fieldName;
		}
		$fieldNamePrefix = (string)$this->viewHelperVariableContainer->get('Tx_Fluid_ViewHelpers_FormViewHelper', 'fieldNamePrefix');
		if ($fieldNamePrefix === '') {
			return $fieldName;
		}
		$fieldNameSegments = explode('[', $fieldName, 2);
		$fieldName = $fieldNamePrefix . '[' . $fieldNameSegments[0] . ']';
		if (count($fieldNameSegments) > 1) {
			$fieldName .= '[' . $fieldNameSegments[1];
		}
		return $fieldName;
	}

	/**
	 * Renders a hidden form field containing the technical identity of the given object.
	 *
	 * @param object $object Object to create the identity field for
	 * @param string $name Name
	 * @return string A hidden field containing the Identity (UID in FLOW3, uid in Extbase) of the given object or NULL if the object is unknown to the persistence framework
	 * @see Tx_Fluid_MVC_Controller_Argument::setValue()
	 */
	protected function renderHiddenIdentityField($object, $name) {
		if (!is_object($object)
			|| !($object instanceof Tx_Extbase_DomainObject_AbstractDomainObject)
			|| ($object->_isNew() && !$object->_isClone())) {
			return '';
		}
		// Intentionally NOT using PersistenceManager::getIdentifierByObject here!!
		// Using that one breaks re-submission of data in forms in case of an error.
		$identifier = $object->getUid();
		if ($identifier === NULL) {
			return chr(10) . '<!-- Object of type ' . get_class($object) . ' is without identity -->' . chr(10);
		}
		$name = $this->prefixFieldName($name) . '[__identity]';
		$this->registerFieldNameForFormTokenGeneration($name);

		return chr(10) . '<input type="hidden" name="'. $name . '" value="' . $identifier .'" />' . chr(10);
	}

	/**
	 * Register a field name for inclusion in the HMAC / Form Token generation
	 *
	 * @param string $fieldName name of the field to register
	 * @return void
	 */
	protected function registerFieldNameForFormTokenGeneration($fieldName) {
		if ($this->viewHelperVariableContainer->exists('Tx_Fluid_ViewHelpers_FormViewHelper', 'formFieldNames')) {
			$formFieldNames = $this->viewHelperVariableContainer->get('Tx_Fluid_ViewHelpers_FormViewHelper', 'formFieldNames');
		} else {
			$formFieldNames = array();
		}
		$formFieldNames[] = $fieldName;
		$this->viewHelperVariableContainer->addOrUpdate('Tx_Fluid_ViewHelpers_FormViewHelper', 'formFieldNames', $formFieldNames);
	}
}

?>