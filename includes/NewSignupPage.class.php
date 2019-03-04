<?php
/**
 * NewSignupPage extension for MediaWiki -- enhances the default signup form
 *
 * @file
 * @ingroup Extensions
 * @author Jack Phoenix
 * @copyright Copyright © 2008-2019 Jack Phoenix
 * @license GPL-2.0-or-later
 */
class NewSignupPage {

	/**
	 * Add the JavaScript file to the page output on the signup page.
	 *
	 * @param OutputPage $out
	 * @param Skin $skin
	 */
	public static function onBeforePageDisplay( &$out, &$skin ) {
		$title = $out->getTitle();

		// Only do our magic if we're on the signup page
		if ( $title->isSpecial( 'CreateAccount' ) ) {
			// It's called Special:CreateAccount since AuthManager (MW 1.27+)
			$out->addModules( 'ext.newsignuppage' );
		}
	}

}
