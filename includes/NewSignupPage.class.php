<?php
/**
 * NewSignupPage extension for MediaWiki -- enhances the default signup form
 *
 * All class methods are public and static.
 *
 * @file
 * @ingroup Extensions
 * @author Jack Phoenix <jack@countervandalism.net>
 * @copyright Copyright © 2008-2016 Jack Phoenix
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */
class NewSignupPage {

	/**
	 * Add the JavaScript file to the page output on the signup page.
	 *
	 * @param OutputPage $out
	 * @param Skin $skin
	 * @return bool
	 */
	public static function onBeforePageDisplay( &$out, &$skin ) {
		$context = $out;
		$title = $context->getTitle();
		$request = $context->getRequest();

		// Only do our magic if we're on the signup page
		if ( $title->isSpecial( 'CreateAccount' ) ) {
			// It's called Special:CreateAccount since AuthManager (MW 1.27+)
			$out->addModules( 'ext.newsignuppage' );
		} elseif ( $title->isSpecial( 'Userlogin' ) ) {
			$kaboom = explode( '/', $title->getText() );
			$signupParamIsSet = false;

			// Catch [[Special:UserLogin/signup]]
			if ( isset( $kaboom[1] ) && $kaboom[1] == 'signup' ) {
				$signupParamIsSet = true;
			}

			// Both index.php?title=Special:UserLogin&type=signup and
			// Special:UserLogin/signup are valid, obviously
			if (
				$request->getVal( 'type' ) == 'signup' ||
				$signupParamIsSet
			)
			{
				$out->addModules( 'ext.newsignuppage' );
			}
		}

		return true;
	}

}