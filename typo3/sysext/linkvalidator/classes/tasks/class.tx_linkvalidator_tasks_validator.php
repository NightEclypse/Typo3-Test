<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 - 2011 Michael Miousse (michael.miousse@infoglobe.ca)
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
 * This class provides Scheduler plugin implementation
 *
 * @author Michael Miousse <michael.miousse@infoglobe.ca>
 * @package TYPO3
 * @subpackage linkvalidator
 */
class tx_linkvalidator_tasks_Validator extends tx_scheduler_Task {

	/**
	 * @var integer
	 */
	protected $sleepTime;

	/**
	 * @var integer
	 */
	protected $sleepAfterFinish;

	/**
	 * @var integer
	 */
	protected $countInARun;

	/**
	 * Total number of broken links
	 *
	 * @var integer
	 */
	protected $totalBrokenLink = 0;

	/**
	 * Total number of broken links from the last run
	 *
	 * @var integer
	 */
	protected $oldTotalBrokenLink = 0;

	/**
	 * Mail template fetched from the given template file
	 *
	 * @var string
	 */
	protected $templateMail;

	/**
	 * specific TSconfig for this task.
	 *
	 * @var array
	 */
	protected $configuration = array();

	/**
	 * Shows if number of result was different from the result of the last check or not
	 *
	 * @var boolean
	 */
	protected $dif;

	/**
	 * Template to be used for the email
	 *
	 * @var string
	 */
	protected $emailTemplateFile;

	/**
	 * Level of pages the task should check
	 *
	 * @var integer
	 */
	protected $depth;

	/**
	 * UID of the start page for this task
	 *
	 * @var integer
	 */
	protected $page;

	/**
	 * Email address to which an email report is sent
	 *
	 * @var string
	 */
	protected $email;

	/**
	 * Only send an email, if new broken links were found
	 *
	 * @var boolean
	 */
	protected $emailOnBrokenLinkOnly;

	/**
	 * Get the value of the protected property email
	 *
	 * @return string Email address to which an email report is sent
	 */
	public function getEmail() {
		return $this->email;
	}

	/**
	 * Set the value of the private property email.
	 *
	 * @param string $email Email address to which an email report is sent
	 * @return void
	 */
	public function setEmail($email) {
		$this->email = $email;
	}

	/**
	 * Get the value of the protected property emailOnBrokenLinkOnly
	 *
	 * @return boolean Whether to send an email, if new broken links were found
	 */
	public function getEmailOnBrokenLinkOnly() {
		return $this->emailOnBrokenLinkOnly;
	}

	/**
	 * Set the value of the private property emailOnBrokenLinkOnly
	 *
	 * @param boolean $emailOnBrokenLinkOnly Only send an email, if new broken links were found
	 * @return void
	 */
	public function setEmailOnBrokenLinkOnly($emailOnBrokenLinkOnly) {
		$this->emailOnBrokenLinkOnly = $emailOnBrokenLinkOnly;
	}

	/**
	 * Get the value of the protected property page
	 *
	 * @return integer UID of the start page for this task
	 */
	public function getPage() {
		return $this->page;
	}

	/**
	 * Set the value of the private property page
	 *
	 * @param integer $page UID of the start page for this task.
	 * @return void
	 */
	public function setPage($page) {
		$this->page = $page;
	}

	/**
	 * Get the value of the protected property depth
	 *
	 * @return integer Level of pages the task should check
	 */
	public function getDepth() {
		return $this->depth;
	}

	/**
	 * Set the value of the private property depth
	 *
	 * @param integer $depth Level of pages the task should check
	 * @return void
	 */
	public function setDepth($depth) {
		$this->depth = $depth;
	}

	/**
	 * Get the value of the protected property emailTemplateFile
	 *
	 * @return string Template to be used for the email
	 */
	public function getEmailTemplateFile() {
		return $this->emailTemplateFile;
	}

	/**
	 * Set the value of the private property emailTemplateFile
	 *
	 * @param string $emailTemplateFile Template to be used for the email
	 * @return void
	 */
	public function setEmailTemplateFile($emailTemplateFile) {
		$this->emailTemplateFile = $emailTemplateFile;
	}

	/**
	 * Get the value of the protected property configuration
	 *
	 * @return array specific TSconfig for this task
	 */
	public function getConfiguration() {
		return $this->configuration;
	}

	/**
	 * Set the value of the private property configuration
	 *
	 * @param array $configuration specific TSconfig for this task
	 * @return void
	 */
	public function setConfiguration($configuration) {
		$this->configuration = $configuration;
	}


	/**
	 * Function execute from the Scheduler
	 *
	 * @return boolean TRUE on successful execution, FALSE on error
	 */
	public function execute() {
		$this->setCliArguments();
		$successfullyExecuted = TRUE;
		if (!file_exists($file = t3lib_div::getFileAbsFileName($this->emailTemplateFile)) && !empty($this->email)) {
			throw new Exception(
				$GLOBALS['LANG']->sL('LLL:EXT:linkvalidator/locallang.xml:tasks.error.invalidEmailTemplateFile'),
				'1295476972'
			);
		}
		$htmlFile = t3lib_div::getURL($file);
		$this->templateMail = t3lib_parsehtml::getSubpart($htmlFile, '###REPORT_TEMPLATE###');

			// The array to put the content into
		$html = array();
		$pageSections = '';
		$this->dif = FALSE;
		$pageList = t3lib_div::trimExplode(',', $this->page, 1);
		$modTS = $this->loadModTSconfig($this->page);
		if (is_array($pageList)) {
			foreach ($pageList as $page) {
				$pageSections .= $this->checkPageLinks($page);
			}
		}
		if ($this->totalBrokenLink != $this->oldTotalBrokenLink) {
			$this->dif = TRUE;
		}
		if ($this->totalBrokenLink > 0
			&& (!$this->emailOnBrokenLinkOnly || $this->dif)
			&& !empty($this->email)
		) {
			$successfullyExecuted = $this->reportEmail($pageSections, $modTS);
		}
		return $successfullyExecuted;
	}

	/**
	 * Validate all links for a page based on the task configuration
	 *
	 * @param integer $page Uid of the page to parse
	 * @return string $pageSections Content of page section
	 */
	protected function checkPageLinks($page) {
		$page = intval($page);
		$pageSections = '';
		$pageIds = '';
		$oldLinkCounts = array();

		$modTS = $this->loadModTSconfig($page);
		$searchFields = $this->getSearchField($modTS);
		$linkTypes = $this->getLinkTypes($modTS);

			/** @var tx_linkvalidator_processor $processor */
		$processor = t3lib_div::makeInstance('tx_linkvalidator_Processor');

		if ($page === 0) {
			$rootLineHidden = FALSE;
		} else {
			$pageRow = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', 'pages', 'uid=' . $page);
			$rootLineHidden = $processor->getRootLineIsHidden($pageRow);
		}

		if (!$rootLineHidden || $modTS['checkhidden'] == 1) {
			$pageIds = $processor->extGetTreeList($page, $this->depth, 0, '1=1', $modTS['checkhidden']);
			if ($pageRow['hidden'] == 0 || $modTS['checkhidden'] == 1) {
					// tx_linkvalidator_Processor::extGetTreeList always adds trailing comma:
				$pageIds .= $page;
			}
		}

		if (!empty($pageIds)) {
			$processor->init($searchFields, $pageIds);

			if (!empty($this->email)) {
				$oldLinkCounts = $processor->getLinkCounts($page);
				$this->oldTotalBrokenLink += $oldLinkCounts['brokenlinkCount'];
			}

			$processor->getLinkStatistics($linkTypes, $modTS['checkhidden']);

			if (!empty($this->email)) {
				$linkCounts = $processor->getLinkCounts($page);
				$this->totalBrokenLink += $linkCounts['brokenlinkCount'];
				$pageSections = $this->buildMail($page, $pageIds, $linkCounts, $oldLinkCounts);
			}
		}

		return $pageSections;
	}

	/**
	 * Get the linkvalidator modTSconfig for a page
	 *
	 * @param integer $page Uid of the page
	 * @return array $modTS mod.linkvalidator TSconfig array
	 */
	protected function loadModTSconfig($page) {
		$modTS = t3lib_BEfunc::getModTSconfig($page, 'mod.linkvalidator');
		$parseObj = t3lib_div::makeInstance('t3lib_TSparser');
		$parseObj->parse($this->configuration);
		if (count($parseObj->errors) > 0) {
			$parseErrorMessage = $GLOBALS['LANG']->sL('LLL:EXT:linkvalidator/locallang.xml:tasks.error.invalidTSconfig') . '<br />';
			foreach ($parseObj->errors as $errorInfo) {
				$parseErrorMessage .= $errorInfo[0] . '<br />';
			}
			throw new Exception(
				$parseErrorMessage,
				'1295476989'
			);
		}
		$TSconfig = $parseObj->setup;
		$modTS = $modTS['properties'];
		$overrideTs = $TSconfig['mod.']['tx_linkvalidator.'];
		if (is_array($overrideTs)) {
			$modTS = t3lib_div::array_merge_recursive_overrule($modTS, $overrideTs);
		}
		return $modTS;
	}

	/**
	 * Get the list of fields to parse in modTSconfig
	 *
	 * @param array $modTS mod.linkvalidator TSconfig array
	 * @return array $searchFields List of fields
	 */
	protected function getSearchField(array $modTS) {
		// Get the searchFields from TypoScript
		foreach ($modTS['searchFields.'] as $table => $fieldList) {
			$fields = t3lib_div::trimExplode(',', $fieldList);
			foreach ($fields as $field) {
				$searchFields[$table][] = $field;
			}
		}
		return isset($searchFields) ? $searchFields : array();
	}

	/**
	 * Get the list of linkTypes to parse in modTSconfig
	 *
	 * @param array $modTS mod.linkvalidator TSconfig array
	 * @return array $linkTypes list of link types
	 */
	protected function getLinkTypes(array $modTS) {
		$linkTypes = array();
		$typesTmp = t3lib_div::trimExplode(',', $modTS['linktypes'], 1);
		if (is_array($typesTmp)) {
			if (!empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'])
				&& is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'] as $type => $value) {
					if (in_array($type, $typesTmp)) {
						$linkTypes[$type] = 1;
					}
				}
			}
		}
		return $linkTypes;
	}

	/**
	 * Build and send warning email when new broken links were found
	 *
	 * @param string $pageSections Content of page section
	 * @param array $modTS TSconfig array
	 * @return boolean TRUE if mail was sent, FALSE if or not
	 */
	protected function reportEmail($pageSections, array $modTS) {
		$content = t3lib_parsehtml::substituteSubpart($this->templateMail, '###PAGE_SECTION###', $pageSections);
		/** @var array $markerArray */
		$markerArray = array();
		/** @var array $validEmailList */
		$validEmailList = array();
		/** @var boolean $sendEmail */
		$sendEmail = TRUE;

		$markerArray['totalBrokenLink'] = $this->totalBrokenLink;
		$markerArray['totalBrokenLink_old'] = $this->oldTotalBrokenLink;

			// Hook
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['reportEmailMarkers'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['reportEmailMarkers'] as $userFunc) {
				$params = array(
					'pObj' => &$this,
					'markerArray' => $markerArray,
				);
				$newMarkers = t3lib_div::callUserFunction($userFunc, $params, $this);
				if (is_array($newMarkers)) {
					$markerArray = t3lib_div::array_merge($markerArray, $newMarkers);
				}
				unset($params);
			}
		}

		$content = t3lib_parsehtml::substituteMarkerArray($content, $markerArray, '###|###', TRUE, TRUE);

		/** @var t3lib_mail_Message $mail */
		$mail = t3lib_div::makeInstance('t3lib_mail_Message');
		if (empty($modTS['mail.']['fromemail'])) {
			$modTS['mail.']['fromemail'] = t3lib_utility_Mail::getSystemFromAddress();
		}
		if (empty($modTS['mail.']['fromname'])) {
			$modTS['mail.']['fromname'] = t3lib_utility_Mail::getSystemFromName();
		}
		if (t3lib_div::validEmail($modTS['mail.']['fromemail'])) {
			$mail->setFrom(array($modTS['mail.']['fromemail'] => $modTS['mail.']['fromname']));
		} else {
			throw new Exception(
				$GLOBALS['LANG']->sL('LLL:EXT:linkvalidator/locallang.xml:tasks.error.invalidFromEmail'),
				'1295476760'
			);
		}
		if (t3lib_div::validEmail($modTS['mail.']['replytoemail'])) {
			$mail->setReplyTo(array($modTS['mail.']['replytoemail'] => $modTS['mail.']['replytoname']));
		}

		if (!empty($modTS['mail.']['subject'])) {
			$mail->setSubject($modTS['mail.']['subject']);
		} else {
			throw new Exception(
				$GLOBALS['LANG']->sL('LLL:EXT:linkvalidator/locallang.xml:tasks.error.noSubject'),
				'1295476808'
			);
		}
		if (!empty($this->email)) {
			$emailList = t3lib_div::trimExplode(',', $this->email);
			foreach ($emailList as $emailAdd) {
				if (!t3lib_div::validEmail($emailAdd)) {
					throw new Exception(
						$GLOBALS['LANG']->sL('LLL:EXT:linkvalidator/locallang.xml:tasks.error.invalidToEmail'),
						'1295476821'
					);
				} else {
					$validEmailList[] = $emailAdd;
				}
			}
		}
		if (is_array($validEmailList) && !empty($validEmailList)) {
			$mail->setTo($this->email);
		} else {
			$sendEmail = FALSE;
		}

		if ($sendEmail) {
			$mail->setBody($content, 'text/html');
			$mail->send();
		}

		return $sendEmail;
	}


	/**
	 * Build the mail content
	 *
	 * @param int $curPage Id of the current page
	 * @param string $pageList List of pages id
	 * @param array $markerArray Array of markers
	 * @param array $oldBrokenLink Marker array with the number of link found
	 * @return string Content of the mail
	 */
	protected function buildMail($curPage, $pageList, array $markerArray, array $oldBrokenLink) {
		$pageSectionHTML = t3lib_parsehtml::getSubpart($this->templateMail, '###PAGE_SECTION###');

			// Hook
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['buildMailMarkers'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['buildMailMarkers'] as $userFunc) {
				$params = array(
					'curPage' => $curPage,
					'pageList' => $pageList,
					'markerArray' => $markerArray,
					'oldBrokenLink' => $oldBrokenLink,
					'pObj' => &$this,
				);
				$newMarkers = t3lib_div::callUserFunction($userFunc, $params, $this);
				if (is_array($newMarkers)) {
					$markerArray = t3lib_div::array_merge($markerArray, $newMarkers);
				}
				unset($params);
			}
		}

		if (is_array($markerArray)) {
			foreach ($markerArray as $markerKey => $markerValue) {
				if (empty($oldBrokenLink[$markerKey])) {
					$oldBrokenLink[$markerKey] = 0;
				}
				if ($markerValue != $oldBrokenLink[$markerKey]) {
					$this->dif = TRUE;
				}
				$markerArray[$markerKey . '_old'] = $oldBrokenLink[$markerKey];
			}
		}
		$markerArray['title'] = t3lib_BEfunc::getRecordTitle('pages', t3lib_BEfunc::getRecord('pages', $curPage));

		$content = '';
		if ($markerArray['brokenlinkCount'] > 0) {
			$content = t3lib_parsehtml::substituteMarkerArray($pageSectionHTML, $markerArray, '###|###', TRUE, TRUE);
		}
		return $content;
	}


	/**
	 * Simulate cli call with setting the required options to the $_SERVER['argv']
	 *
	 * @return void
	 */
	protected function setCliArguments() {
		$_SERVER['argv'] = array(
			$_SERVER['argv'][0],
			'tx_link_scheduler_link',
			'0',
			'-ss',
			'--sleepTime',
			$this->sleepTime,
			'--sleepAfterFinish',
			$this->sleepAfterFinish,
			'--countInARun',
			$this->countInARun
		);
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/linkvalidator/classes/tasks/class.tx_linkvalidator_tasks_validator.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/linkvalidator/classes/tasks/class.tx_linkvalidator_tasks_validator.php']);
}
?>
