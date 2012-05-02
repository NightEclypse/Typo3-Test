<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2011 Stanislas Rolland <typo3(arobas)sjbr.ca>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
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
 * DefaultInline plugin for htmlArea RTE
 *
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 *
 */
class tx_rtehtmlarea_defaultinline extends tx_rtehtmlarea_api {

	protected $extensionKey = 'rtehtmlarea';	// The key of the extension that is extending htmlArea RTE
	protected $pluginName = 'DefaultInline';	// The name of the plugin registered by the extension
	protected $relativePathToLocallangFile = 'extensions/DefaultInline/locallang.xml';	// Path to this main locallang file of the extension relative to the extension dir.
	protected $relativePathToSkin = 'extensions/DefaultInline/skin/htmlarea.css';		// Path to the skin (css) file relative to the extension dir.
	protected $htmlAreaRTE;				// Reference to the invoking object
	protected $thisConfig;				// Reference to RTE PageTSConfig
	protected $toolbar;				// Reference to RTE toolbar array
	protected $LOCAL_LANG; 				// Frontend language array

	protected $pluginButtons = 'bold,italic,strikethrough,subscript,superscript,underline';
	protected $convertToolbarForHtmlAreaArray = array (
		'bold'			=> 'Bold',
		'italic'		=> 'Italic',
		'underline'		=> 'Underline',
		'strikethrough'		=> 'StrikeThrough',
		'superscript'		=> 'Superscript',
		'subscript'		=> 'Subscript',
		);

	/**
	 * Return JS configuration of the htmlArea plugins registered by the extension
	 *
	 * @param	integer		Relative id of the RTE editing area in the form
	 *
	 * @return string		JS configuration for registered plugins
	 *
	 * The returned string will be a set of JS instructions defining the configuration that will be provided to the plugin(s)
	 * Each of the instructions should be of the form:
	 * 	RTEarea['.$RTEcounter.']["buttons"]["button-id"]["property"] = "value";
	 */
	public function buildJavascriptConfiguration($RTEcounter) {
		global $TSFE, $LANG;

		$registerRTEinJavascriptString = '';
		return $registerRTEinJavascriptString;
	}

	/**
	 * Return tranformed content
	 *
	 * @param	string		$content: The content that is about to be sent to the RTE
	 *
	 * @return 	string		the transformed content
	 */
	public function transformContent($content) {

			// Change the strong and em tags for gecko browsers
		if ($this->htmlAreaRTE->client['browser'] == 'gecko') {
				// change <strong> to <b>
			$content = preg_replace('/<(\/?)strong/i', "<$1b", $content);
				// change <em> to <i>
			$content = preg_replace('/<(\/?)em([^b>]*>)/i', "<$1i$2", $content);
		}

		return $content;
	}

} // end of class

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/extensions/DefaultInline/class.tx_rtehtmlarea_defaultinline.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/extensions/DefaultInline/class.tx_rtehtmlarea_defaultinline.php']);
}

?>