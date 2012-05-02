<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2011 Andreas Wolf <andreas.wolf@ikt-werk.de>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * TCEforms wizard for rendering an AJAX selector for records
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @author Benjamin Mack <benni@typo3.org>
 */

class t3lib_TCEforms_Suggest {
		// count the number of ajax selectors used
	public $suggestCount = 0;
	public $cssClass = 'typo3-TCEforms-suggest';
	public $TCEformsObj; // reference to t3lib_tceforms


	/**
	 * Initialize an instance of t3lib_TCEforms_suggest
	 *
	 * @param  t3lib_TCEforms  $tceForms  Reference to an TCEforms instance
	 * @return void
	 */
	public function init(&$tceForms) {
		$this->TCEformsObj =& $tceForms;
	}

	/**
	 * Renders an ajax-enabled text field. Also adds required JS
	 *
	 * @param string $fieldname The fieldname in the form
	 * @param string $table The table we render this selector for
	 * @param string $field The field we render this selector for
	 * @param array $row The row which is currently edited
	 * @param array $config The TSconfig of the field
	 * @return string The HTML code for the selector
	 */
	public function renderSuggestSelector($fieldname, $table, $field, array $row, array $config) {
		$this->suggestCount++;

		$containerCssClass = $this->cssClass . ' ' . $this->cssClass . '-position-right';
		$suggestId = 'suggest-' . $table . '-' . $field . '-' . $row['uid'];

		if ($GLOBALS['TCA'][$table]['columns'][$field]['config']['type'] === 'flex') {
			$fieldPattern = 'data[' . $table . '][' . $row['uid'] . '][';
			$flexformField = str_replace($fieldPattern, '', $fieldname);
			$flexformField = substr($flexformField, 0, -1);
			$field = str_replace(array(']['), '|', $flexformField);
		}

		$selector = '
		<div class="' . $containerCssClass . '" id="' . $suggestId . '">
			<input type="text" id="' . $fieldname . 'Suggest" value="' .
					$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.findRecord') . '" class="' . $this->cssClass . '-search" />
			<div class="' . $this->cssClass . '-indicator" style="display: none;" id="' . $fieldname . 'SuggestIndicator">
				<img src="' . $GLOBALS['BACK_PATH'] . 'gfx/spinner.gif" alt="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:alttext.suggestSearching') . '" />
			</div>
			<div class="' . $this->cssClass . '-choices" style="display: none;" id="' . $fieldname . 'SuggestChoices"></div>

		</div>';

			// get minimumCharacters from TCA
		if (isset($config['fieldConf']['config']['wizards']['suggest']['default']['minimumCharacters'])) {
			$minChars = intval($config['fieldConf']['config']['wizards']['suggest']['default']['minimumCharacters']);
		}
			// overwrite it with minimumCharacters from TSConfig (TCEFORM) if given
		if (isset($config['fieldTSConfig']['suggest.']['default.']['minimumCharacters'])) {
			$minChars = intval($config['fieldTSConfig']['suggest.']['default.']['minimumCharacters']);
		}
		$minChars = ($minChars > 0 ? $minChars : 2);

			// replace "-" with ucwords for the JS object name
		$jsObj = str_replace(' ', '', ucwords(str_replace('-', ' ', t3lib_div::strtolower($suggestId))));
		$this->TCEformsObj->additionalJS_post[] = '
			var ' . $jsObj . ' = new TCEForms.Suggest("' . $fieldname . '", "' . $table . '", "' . $field .
												  '", "' . $row['uid'] . '", ' . $row['pid'] . ', ' . $minChars . ');
			' . $jsObj . '.defaultValue = "' . t3lib_div::slashJS($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.findRecord')) . '";
		';

		return $selector;
	}

	/**
	 * Ajax handler for the "suggest" feature in TCEforms.
	 *
	 * @param array $params The parameters from the AJAX call
	 * @param TYPO3AJAX $ajaxObj The AJAX object representing the AJAX call
	 * @return void
	 */
	public function processAjaxRequest($params, &$ajaxObj) {

			// get parameters from $_GET/$_POST
		$search = t3lib_div::_GP('value');
		$table = t3lib_div::_GP('table');
		$field = t3lib_div::_GP('field');
		$uid = t3lib_div::_GP('uid');
		$pageId = t3lib_div::_GP('pid');

		t3lib_div::loadTCA($table);

			// If the $uid is numeric, we have an already existing element, so get the
			// TSconfig of the page itself or the element container (for non-page elements)
			// otherwise it's a new element, so use given id of parent page (i.e., don't modify it here)
		if (is_numeric($uid)) {
			if ($table == 'pages') {
				$pageId = $uid;
			} else {
				$row = t3lib_BEfunc::getRecord($table, $uid);
				$pageId = $row['pid'];
			}
		}

		$TSconfig = t3lib_BEfunc::getPagesTSconfig($pageId);
		$queryTables = array();
		$foreign_table_where = '';

		$fieldConfig = $GLOBALS['TCA'][$table]['columns'][$field]['config'];

		$parts = explode('|', $field);
		if ($GLOBALS['TCA'][$table]['columns'][$parts[0]]['config']['type'] === 'flex') {
			if (is_array($row) && (count($row) > 0)) {
				$flexfieldTCAConfig = $GLOBALS['TCA'][$table]['columns'][$parts[0]]['config'];
				$flexformDSArray = t3lib_BEfunc::getFlexFormDS($flexfieldTCAConfig, $row, $table);
				$flexformDSArray = t3lib_div::resolveAllSheetsInDS($flexformDSArray);
				$flexformElement = $parts[count($parts) - 2];
				$continue = TRUE;
				foreach ($flexformDSArray as $sheet) {
					foreach ($sheet as $_ => $dataStructure) {
						if (isset($dataStructure['ROOT']['el'][$flexformElement]['TCEforms']['config'])) {
							$fieldConfig = $dataStructure['ROOT']['el'][$flexformElement]['TCEforms']['config'];
							$continue = FALSE;
							break;
						}
					}
					if (!$continue) {
						break;
					}
				}
				$field = str_replace('|', '][', $field);
			}
		}

		$wizardConfig = $fieldConfig['wizards']['suggest'];

		if (isset($fieldConfig['allowed'])) {
			$queryTables = t3lib_div::trimExplode(',', $fieldConfig['allowed']);
		} elseif (isset($fieldConfig['foreign_table'])) {
			$queryTables = array($fieldConfig['foreign_table']);
			$foreign_table_where = $fieldConfig['foreign_table_where'];
				// strip ORDER BY clause
			$foreign_table_where = trim(preg_replace('/ORDER[[:space:]]+BY.*/i', '', $foreign_table_where));
		}
		$resultRows = array();

			// fetch the records for each query table. A query table is a table from which records are allowed to
			// be added to the TCEForm selector, originally fetched from the "allowed" config option in the TCA
		foreach ($queryTables as $queryTable) {
			t3lib_div::loadTCA($queryTable);

				// if the table does not exist, skip it
			if (!is_array($GLOBALS['TCA'][$queryTable]) || !count($GLOBALS['TCA'][$queryTable])) {
				continue;
			}
			$config = (array) $wizardConfig['default'];

			if (is_array($wizardConfig[$queryTable])) {
				$config = t3lib_div::array_merge_recursive_overrule($config, $wizardConfig[$queryTable]);
			}


				// merge the configurations of different "levels" to get the working configuration for this table and
				// field (i.e., go from the most general to the most special configuration)
			if (is_array($TSconfig['TCEFORM.']['suggest.']['default.'])) {
				$config = t3lib_div::array_merge_recursive_overrule($config, $TSconfig['TCEFORM.']['suggest.']['default.']);
			}

			if (is_array($TSconfig['TCEFORM.']['suggest.'][$queryTable . '.'])) {
				$config = t3lib_div::array_merge_recursive_overrule($config, $TSconfig['TCEFORM.']['suggest.'][$queryTable . '.']);
			}

				// use $table instead of $queryTable here because we overlay a config
				// for the input-field here, not for the queried table
			if (is_array($TSconfig['TCEFORM.'][$table . '.'][$field . '.']['suggest.']['default.'])) {
				$config = t3lib_div::array_merge_recursive_overrule($config, $TSconfig['TCEFORM.'][$table . '.'][$field . '.']['suggest.']['default.']);
			}
			if (is_array($TSconfig['TCEFORM.'][$table . '.'][$field . '.']['suggest.'][$queryTable . '.'])) {
				$config = t3lib_div::array_merge_recursive_overrule($config, $TSconfig['TCEFORM.'][$table . '.'][$field . '.']['suggest.'][$queryTable . '.']);
			}

				//process addWhere
			if (!isset($config['addWhere']) && $foreign_table_where) {
				$config['addWhere'] = $foreign_table_where;
			}
			if (isset($config['addWhere'])) {
				$config['addWhere'] = strtr(' ' . $config['addWhere'], array(
																			'###THIS_UID###' => intval($uid),
																			'###CURRENT_PID###' => intval($pageId),
																	   ));
			}
				// instantiate the class that should fetch the records for this $queryTable
			$receiverClassName = $config['receiverClass'];
			if (!class_exists($receiverClassName)) {
				$receiverClassName = 't3lib_TCEforms_Suggest_DefaultReceiver';
			}
			$receiverObj = t3lib_div::makeInstance($receiverClassName, $queryTable, $config);

			$params = array('value' => $search);
			$rows = $receiverObj->queryTable($params);

			if (empty($rows)) {
				continue;
			}
			$resultRows = t3lib_div::array_merge($resultRows, $rows);
			unset($rows);
		}

		$listItems = array();
		if (count($resultRows) > 0) {
				// traverse all found records and sort them
			$rowsSort = array();
			foreach ($resultRows as $key => $row) {
				$rowsSort[$key] = $row['text'];
			}
			asort($rowsSort);
			$rowsSort = array_keys($rowsSort);

				// Limit the number of items in the result list
			$maxItems = $config['maxItemsInResultList'] ? $config['maxItemsInResultList'] : 10;
			$maxItems = min(count($resultRows), $maxItems);

				// put together the selector entry
			for ($i = 0; $i < $maxItems; $i++) {
				$row = $resultRows[$rowsSort[$i]];
				$rowId = $row['table'] . '-' . $row['uid'] . '-' . $table . '-' . $uid . '-' . $field;
				$listItems[] = '<li' . ($row['class'] != '' ? ' class="' . $row['class'] . '"' : '') .
							   ' id="' . $rowId . '" style="' . $row['style'] . '">' . $row['text'] . '</li>';
			}
		}

		if (count($listItems) > 0) {
			$list = implode('', $listItems);
		} else {
			$list = '<li class="suggest-noresults"><i>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.noRecordFound') . '</i></li>';
		}

		$list = '<ul class="' . $this->cssClass . '-resultlist">' . $list . '</ul>';
		$ajaxObj->addContent(0, $list);
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['classes/t3lib/tceforms/class.t3lib_tceforms_suggest.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['classes/t3lib/tceforms/class.t3lib_tceforms_suggest.php']);
}

?>