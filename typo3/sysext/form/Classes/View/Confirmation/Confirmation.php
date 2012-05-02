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
 * Main view layer for Forms.
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_View_Confirmation extends tx_form_View_Confirmation_Element_Container {

	/**
	 * Default layout of this object
	 *
	 * @var string
	 */
	protected $layout = '
		<containerWrap />';

	/**
	 * The TypoScript settings for the confirmation
	 *
	 * @var array
	 */
	protected $typoscript = array();

	/**
	 * The localization handler
	 *
	 * @var tx_form_System_Localization
	 */
	protected $localizationHandler;

	/**
	 * The content object
	 *
	 * @var tslib_cObj
	 */
	protected $localCobj;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct(tx_form_Domain_Model_Form $model, array $typoscript) {
		$this->localCobj = t3lib_div::makeInstance('tslib_cObj');
		$this->localizationHandler = t3lib_div::makeInstance(
			'tx_form_System_Localization'
		);
		$this->typoscript = $typoscript;
		parent::__construct($model);
	}

	/**
	 * Set the data for the FORM tag
	 *
	 * @param tx_form_Domain_Model_Form $formModel The model of the form
	 * @return void
	 */
	public function setData(tx_form_Domain_Model_Form $model) {
		$this->model = (object) $model;
	}

	/**
	 * Start the main DOMdocument for the form
	 * Return it as a string using saveXML() to get a proper formatted output
	 * (when using formatOutput :-)
	 *
	 * @return string XHTML string containing the whole form
	 */
	public function get() {
		$this->setCss();
		$message = $this->getMessage();

		$node = $this->render('element', FALSE);
		$formInput = chr(10) .
			html_entity_decode(
				$node->saveXML($node->firstChild),
				ENT_QUOTES,
				'UTF-8'
			) .
			chr(10);

		$confirmationButtons = $this->getConfirmationButtons();

		$content = $message . chr(10) . $formInput . chr(10) . $confirmationButtons;

		return $content;
	}

	/**
	 * Construct the message
	 *
	 * The message is a cObj, which can be overriden using the typoscript
	 * setting confirmation.message, like
	 *
	 * confirmation.message = TEXT
	 * confirmation.message.value = Here some text
	 * confirmation.message.wrap = <p>|</p>
	 *
	 * @return string XHTML string containing the message
	 */
	protected function getMessage() {
		if (isset($this->typoscript['message']) && isset($this->typoscript['message.'])) {
			$value = $this->typoscript['message.'];
			$type = $this->typoscript['message'];
		} elseif (isset($this->typoscript['message.'])) {
			$value = $this->typoscript['message.'];
			$type = 'TEXT';
		} else {
			$value['wrap'] = '<p>|</p>';
			$value['value'] = $this->localizationHandler->getLocalLanguageLabel('tx_form_view_confirmation.message');
			$type = 'TEXT';
		}

		return $this->localCobj->cObjGetSingle($type, $value);
	}

	protected function getConfirmationButtons() {
		$requestHandler = t3lib_div::makeInstance('tx_form_System_Request');
		$prefix = $requestHandler->getPrefix();
		$action = $this->localCobj->getTypoLink_URL($GLOBALS['TSFE']->id);

		$confirmationButtons = '
			<form class="csc-form-confirmation" method="post" action="' . $action . '">
				<fieldset>
					<ol>
						<li class="csc-form-confirmation-false">
							<input type="submit" value="' .
								$this->localizationHandler->getLocalLanguageLabel('tx_form_view_confirmation.donotconfirm') .
								'" name="' . $prefix . '[confirmation-false]" />
						</li>
						<li class="csc-form-confirmation-true">
							<input type="submit" value="' .
								$this->localizationHandler->getLocalLanguageLabel('tx_form_view_confirmation.confirm') .
								'" name="' . $prefix . '[confirmation-true]" />
						</li>
					</ol>
				</fieldset>
			</form>
		';

		return $confirmationButtons;
	}

	/**
	 * Add the form CSS file as additional header data
	 *
	 * @return void
	 */
	protected function setCss() {
		$GLOBALS['TSFE']->additionalHeaderData['tx_form_css'] =
			'<link rel="stylesheet" type="text/css" href="' .
			t3lib_extMgm::siteRelPath('form') .
			'Resources/Public/CSS/Confirmation.css' .
			'" media="all" />';
	}
}
?>