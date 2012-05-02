<?php

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 */

/**
 * Email link view helper.
 * Generates an email link incorporating TYPO3s spamProtectEmailAddresses-settings.
 *
 * = Examples
 *
 * <code title="basic email link">
 * <f:link.email email="foo@bar.tld" />
 * </code>
 * <output>
 * <a href="javascript:linkTo_UnCryptMailto('ocknvq,hqqBdct0vnf');">foo(at)bar.tld</a>
 * (depending on your spamProtectEmailAddresses-settings)
 * </output>
 *
 * <code title="Email link with custom linktext">
 * <f:link.email email="foo@bar.tld">some custom content</f:link.email>
 * </code>
 * <output>
 * <a href="javascript:linkTo_UnCryptMailto('ocknvq,hqqBdct0vnf');">some custom content</a>
 * </output>
 *
 */
class Tx_Fluid_ViewHelpers_Link_EmailViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractTagBasedViewHelper {

	/**
	 * @var string
	 */
	protected $tagName = 'a';

	/**
	 * Arguments initialization
	 *
	 * @return void
	 */
	public function initializeArguments() {
		$this->registerUniversalTagAttributes();
		$this->registerTagAttribute('name', 'string', 'Specifies the name of an anchor');
		$this->registerTagAttribute('rel', 'string', 'Specifies the relationship between the current document and the linked document');
		$this->registerTagAttribute('rev', 'string', 'Specifies the relationship between the linked document and the current document');
		$this->registerTagAttribute('target', 'string', 'Specifies where to open the linked document');
	}

	/**
	 * @param string $email The email address to be turned into a link.
	 * @return string Rendered email link
	 */
	public function render($email) {
		if (TYPO3_MODE === 'FE') {
			list($linkHref, $linkText) = $GLOBALS['TSFE']->cObj->getMailTo($email, $email);
		} else {
			$linkHref = 'mailto:' . $email;
			$linkText = $email;
		}
		$tagContent = $this->renderChildren();
		if ($tagContent !== NULL) {
			$linkText = $tagContent;
		}
		$this->tag->setContent($linkText);
		$this->tag->addAttribute('href', $linkHref);
		$this->tag->forceClosingTag(TRUE);

		return $this->tag->render();
	}
}


?>