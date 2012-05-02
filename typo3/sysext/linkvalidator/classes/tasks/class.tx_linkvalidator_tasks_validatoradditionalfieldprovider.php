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
 * This class provides Scheduler Additional Field plugin implementation
 *
 * @author Dimitri König <dk@cabag.ch>
 * @author Michael Miousse <michael.miousse@infoglobe.ca>
 * @package TYPO3
 * @subpackage linkvalidator
 */
class tx_linkvalidator_tasks_ValidatorAdditionalFieldProvider implements tx_scheduler_AdditionalFieldProvider {

	/**
	 * Render additional information fields within the scheduler backend.
	 *
	 * @param array $taskInfo Array information of task to return
	 * @param task $task Task object
	 * @param tx_scheduler_Module $schedulerModule Reference to the calling object (BE module of the Scheduler)
	 * @return array Additional fields
	 * @see interfaces/tx_scheduler_AdditionalFieldProvider#getAdditionalFields($taskInfo, $task, $schedulerModule)
	 */
	public function getAdditionalFields(array &$taskInfo, $task, tx_scheduler_Module $schedulerModule) {
		$additionalFields = array();
		if (empty($taskInfo['configuration'])) {
			if ($schedulerModule->CMD == 'add') {
				$taskInfo['configuration'] = '';
			} elseif ($schedulerModule->CMD == 'edit') {
				$taskInfo['configuration'] = $task->getConfiguration();
			} else {
				$taskInfo['configuration'] = $task->getConfiguration();
			}
		}

		if (empty($taskInfo['depth'])) {
			if ($schedulerModule->CMD == 'add') {
				$taskInfo['depth'] = array();
			} elseif ($schedulerModule->CMD == 'edit') {
				$taskInfo['depth'] = $task->getDepth();
			} else {
				$taskInfo['depth'] = $task->getDepth();
			}
		}

		if (empty($taskInfo['page'])) {
			if ($schedulerModule->CMD == 'add') {
				$taskInfo['page'] = '';
			} elseif ($schedulerModule->CMD == 'edit') {
				$taskInfo['page'] = $task->getPage();
			} else {
				$taskInfo['page'] = $task->getPage();
			}
		}
		if (empty($taskInfo['email'])) {
			if ($schedulerModule->CMD == 'add') {
				$taskInfo['email'] = '';
			} elseif ($schedulerModule->CMD == 'edit') {
				$taskInfo['email'] = $task->getEmail();
			} else {
				$taskInfo['email'] = $task->getEmail();
			}
		}

		if (empty($taskInfo['emailOnBrokenLinkOnly'])) {
			if ($schedulerModule->CMD == 'add') {
				$taskInfo['emailOnBrokenLinkOnly'] = 1;
			} elseif ($schedulerModule->CMD == 'edit') {
				$taskInfo['emailOnBrokenLinkOnly'] = $task->getEmailOnBrokenLinkOnly();
			} else {
				$taskInfo['emailOnBrokenLinkOnly'] = $task->getEmailOnBrokenLinkOnly();
			}
		}
		if (empty($taskInfo['emailTemplateFile'])) {
			if ($schedulerModule->CMD == 'add') {
				$taskInfo['emailTemplateFile'] = 'EXT:linkvalidator/res/mailtemplate.html';
			} elseif ($schedulerModule->CMD == 'edit') {
				$taskInfo['emailTemplateFile'] = $task->getEmailTemplateFile();
			} else {
				$taskInfo['emailTemplateFile'] = $task->getEmailTemplateFile();
			}
		}


		$fieldID = 'task_page';
		$fieldCode = '<input type="text" name="tx_scheduler[linkvalidator][page]"  id="' . $fieldID . '" value="'
					 . htmlspecialchars($taskInfo['page']) . '"/>';
		$label = $GLOBALS['LANG']->sL('LLL:EXT:linkvalidator/locallang.xml:tasks.validate.page');
		$label = t3lib_BEfunc::wrapInHelp('linkvalidator', $fieldID, $label);
		$additionalFields[$fieldID] = array(
			'code' => $fieldCode,
			'label' => $label
		);

			// input for depth
		$fieldID = 'task_depth';
		$fieldValueArray = array(
			'0' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.depth_0'),
			'1' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.depth_1'),
			'2' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.depth_2'),
			'3' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.depth_3'),
			'4' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.depth_4'),
			'999' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.depth_infi'),
		);
		$fieldCode = '<select name="tx_scheduler[linkvalidator][depth]" id="' . $fieldID . '">';

		foreach ($fieldValueArray as $depth => $label) {
			$fieldCode .= "\t" . '<option value="' . htmlspecialchars($depth) . '"'
						  . (($depth == $taskInfo['depth']) ? ' selected="selected"' : '')
						  . '>' . $label . '</option>';
		}

		$fieldCode .= '</select>';
		$label = $GLOBALS['LANG']->sL('LLL:EXT:linkvalidator/locallang.xml:tasks.validate.depth');
		$label = t3lib_BEfunc::wrapInHelp('linkvalidator', $fieldID, $label);
		$additionalFields[$fieldID] = array(
			'code' => $fieldCode,
			'label' => $label
		);

		$fieldID = 'task_configuration';
		$fieldCode = '<textarea  name="tx_scheduler[linkvalidator][configuration]" id="' . $fieldID . '" >'
					 . htmlspecialchars($taskInfo['configuration']) . '</textarea>';
		$label = $GLOBALS['LANG']->sL('LLL:EXT:linkvalidator/locallang.xml:tasks.validate.conf');
		$label = t3lib_BEfunc::wrapInHelp('linkvalidator', $fieldID, $label);
		$additionalFields[$fieldID] = array(
			'code' => $fieldCode,
			'label' => $label
		);

		$fieldID = 'task_email';
		$fieldCode = '<input type="text"  name="tx_scheduler[linkvalidator][email]" id="' . $fieldID . '" value="'
					 . htmlspecialchars($taskInfo['email']) . '" />';
		$label = $GLOBALS['LANG']->sL('LLL:EXT:linkvalidator/locallang.xml:tasks.validate.email');
		$label = t3lib_BEfunc::wrapInHelp('linkvalidator', $fieldID, $label);
		$additionalFields[$fieldID] = array(
			'code' => $fieldCode,
			'label' => $label
		);
		$fieldID = 'task_emailOnBrokenLinkOnly';
		$fieldCode = '<input type="checkbox"  name="tx_scheduler[linkvalidator][emailOnBrokenLinkOnly]" id="' . $fieldID . '" '
					 . (htmlspecialchars($taskInfo['emailOnBrokenLinkOnly']) ? 'checked="checked"' : '') . ' />';
		$label = $GLOBALS['LANG']->sL('LLL:EXT:linkvalidator/locallang.xml:tasks.validate.emailOnBrokenLinkOnly');
		$label = t3lib_BEfunc::wrapInHelp('linkvalidator', $fieldID, $label);
		$additionalFields[$fieldID] = array(
			'code' => $fieldCode,
			'label' => $label
		);

		$fieldID = 'task_emailTemplateFile';
		$fieldCode = '<input type="text"  name="tx_scheduler[linkvalidator][emailTemplateFile]" id="' . $fieldID
					 . '" value="' . htmlspecialchars($taskInfo['emailTemplateFile']) . '" />';
		$label = $GLOBALS['LANG']->sL('LLL:EXT:linkvalidator/locallang.xml:tasks.validate.emailTemplateFile');
		$label = t3lib_BEfunc::wrapInHelp('linkvalidator', $fieldID, $label);
		$additionalFields[$fieldID] = array(
			'code' => $fieldCode,
			'label' => $label
		);

		return $additionalFields;
	}


	/**
	 * Mark current value as selected by returning the "selected" attribute
	 *
	 * @param array $configurationArray Array of configuration
	 * @param string $currentValue Value of selector object
	 * @return string Html fragment for a selected option or empty
	 */
	protected function getSelectedState(array $configurationArray, $currentValue) {
		$selected = '';
		for ($i = 0; $i < count($configurationArray); $i++) {
			if (strcmp($configurationArray[$i], $currentValue) === 0) {
				$selected = 'selected="selected" ';
			}
		}
		return $selected;
	}


	/**
	 * This method checks any additional data that is relevant to the specific task.
	 * If the task class is not relevant, the method is expected to return TRUE.
	 *
	 * @param array $submittedData Reference to the array containing the data submitted by the user
	 * @param tx_scheduler_Module $schedulerModule Reference to the calling object (BE module of the Scheduler)
	 * @return boolean TRUE if validation was ok (or selected class is not relevant), FALSE otherwise
	 */
	public function validateAdditionalFields(array &$submittedData, tx_scheduler_Module $schedulerModule) {
		$isValid = TRUE;

			//TODO add validation to validate the $submittedData['configuration'] which is normally a comma separated string
		if (!empty($submittedData['linkvalidator']['email'])) {
			$emailList = t3lib_div::trimExplode(',', $submittedData['linkvalidator']['email']);
			foreach ($emailList as $emailAdd) {
				if (!t3lib_div::validEmail($emailAdd)) {
					$isValid = FALSE;
					$schedulerModule->addMessage(
						$GLOBALS['LANG']->sL('LLL:EXT:linkvalidator/locallang.xml:tasks.validate.invalidEmail'),
						t3lib_FlashMessage::ERROR
					);
				}
			}
		}

		if ($res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'pages', 'uid = ' . intval($submittedData['linkvalidator']['page']))) {
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) == 0 && $submittedData['linkvalidator']['page'] > 0) {
				$isValid = FALSE;
				$schedulerModule->addMessage(
					$GLOBALS['LANG']->sL('LLL:EXT:linkvalidator/locallang.xml:tasks.validate.invalidPage'),
					t3lib_FlashMessage::ERROR
				);
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		} else {
			$isValid = FALSE;
			$schedulerModule->addMessage(
				$GLOBALS['LANG']->sL('LLL:EXT:linkvalidator/locallang.xml:tasks.validate.invalidPage'),
				t3lib_FlashMessage::ERROR
			);
		}

		if ($submittedData['linkvalidator']['depth'] < 0) {
			$isValid = FALSE;
			$schedulerModule->addMessage(
				$GLOBALS['LANG']->sL('LLL:EXT:linkvalidator/locallang.xml:tasks.validate.invalidDepth'),
				t3lib_FlashMessage::ERROR
			);
		}

		return $isValid;
	}


	/**
	 * This method is used to save any additional input into the current task object
	 * if the task class matches.
	 *
	 * @param array $submittedData Array containing the data submitted by the user
	 * @param tx_scheduler_Task $task Reference to the current task object
	 * @return void
	 */
	public function saveAdditionalFields(array $submittedData, tx_scheduler_Task $task) {
		$task->setDepth($submittedData['linkvalidator']['depth']);
		$task->setPage($submittedData['linkvalidator']['page']);
		$task->setEmail($submittedData['linkvalidator']['email']);
		if ($submittedData['linkvalidator']['emailOnBrokenLinkOnly']) {
			$task->setEmailOnBrokenLinkOnly(1);
		} else {
			$task->setEmailOnBrokenLinkOnly(0);
		}
		$task->setConfiguration($submittedData['linkvalidator']['configuration']);
		$task->setEmailTemplateFile($submittedData['linkvalidator']['emailTemplateFile']);
	}


}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/linkvalidator/classes/tasks/class.tx_linkvalidator_tasks_validatoradditionalfieldprovider.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/linkvalidator/classes/tasks/class.tx_linkvalidator_tasks_validatoradditionalfieldprovider.php']);
}

?>