<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Marcus Krause <marcus#exp2010@t3sec.info>
 *		   Steffen Kamper <info@sk-typo3.de>
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
 * class.tx_em_parser_xmlparserfactory.php
 *
 * Module: Extension manager - XML parser factory
 *
 * @author  Marcus Krause <marcus#exp2010@t3sec.info>
 * @author  Steffen Kamper <info@sk-typo3.de>
 */

/**
 * Factory for XML parsers.
 *
 * @author	  Marcus Krause <marcus#exp2010@t3sec.info>
 * @author	  Steffen Kamper <info@sk-typo3.de>
 *
 * @since	   2010-02-10
 * @package	 TYPO3
 * @subpackage  EM
 */
class tx_em_Parser_XmlParserFactory {

	/**
	 * An array with instances of xml parsers.
	 * This member is set in the getParserInstance() function.
	 *
	 * @var	array
	 */
	static protected $instance = array();

	/**
	 * Keeps array of all available parsers.
	 *
	 * TODO: This would better be moved to
	 * a global configuration array like
	 * $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'].
	 * (might require EM to be moved in a sysext)
	 *
	 * @var array
	 */
	static protected $parsers = array(
		'extension' => array(
			'tx_em_parser_extensionxmlpullparser' => 'class.tx_em_parser_extensionxmlpullparser.php',
			'tx_em_parser_extensionxmlpushparser' => 'class.tx_em_parser_extensionxmlpushparser.php',
		),
		'mirror' => array(
			'tx_em_parser_mirrorxmlpullparser' => 'class.tx_em_parser_mirrorxmlpullparser.php',
			'tx_em_parser_mirrorxmlpushparser' => 'class.tx_em_parser_mirrorxmlpushparser.php',
		),
	);


	/**
	 * Obtains a xml parser instance.
	 *
	 * This function will return an instance of a class that implements
	 * tx_em_ExtensionXml_Abstract_Parser.
	 *
	 * TODO use autoload if possible (might require EM to be moved in a sysext)
	 *
	 * @access  public
	 * @param   string	  $parserType: type of parser, one of extension and mirror
	 * @param	string		$excludeClassNames: (optional) comma-separated list of class names
	 * @return	tx_em_ExtensionXml_Abstract_Parser	an instance of an extension.xml parser
	 */
	static public function getParserInstance($parserType, $excludeClassNames = '') {
		if (!isset(self::$instance[$parserType]) || !is_object(self::$instance[$parserType]) || !empty($excludeClassNames)) {
			// reset instance
			self::$instance[$parserType] = $objParser = NULL;
			foreach (self::$parsers[$parserType] as $className => $file) {
				if (!t3lib_div::inList($excludeClassNames, $className)) {
					$objParser = t3lib_div::makeInstance($className);
					if ($objParser->isAvailable()) {
						self::$instance[$parserType] = &$objParser;
						break;
					}
					$objParser = NULL;
				}
			}
		}
		return self::$instance[$parserType];
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/sysext/em/classes/parser/class.tx_em_parser_xmlparserfactory.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/sysext/em/classes/parser/class.tx_em_parser_xmlparserfactory.php']);
}

?>