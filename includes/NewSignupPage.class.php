<?php
/**
 * NewSignupPage extension for MediaWiki -- enhances the default signup form
 *
 * @file
 * @ingroup Extensions
 * @author Jack Phoenix
 * @copyright Copyright © 2008-2020 Jack Phoenix
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

	/**
	 * Creates the necessary database table when the user runs
	 * maintenance/update.php, the core MediaWiki updater script, provided that
	 * the configuration specifies us to create it.
	 *
	 * @param $updater DatabaseUpdater
	 * @return bool True when we should not do anything
	 */
	public static function onLoadExtensionSchemaUpdates( $updater ) {
		global $wgRegisterTrack;

		$db = $updater->getDB();

		if ( !$db->tableExists( 'user_register_track' ) && !$wgRegisterTrack ) {
			// Table doesn't exist and shouldn't either -> bail out
			return true;
		}

		$dir = __DIR__ . '/../sql';
		$dbType = $db->getType();
		$file = $dir . '/user_register_track.sql';
		if ( $dbType === 'postgres' ) {
			$file = $dir . '/user_register_track.postgres.sql';
		}

		$updater->addExtensionTable( 'user_register_track', $file );
	}

}
