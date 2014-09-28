<?php
/**
 * NewSignupPage extension for MediaWiki -- enhances the default signup form
 *
 * @file
 * @ingroup Extensions
 * @version 0.6
 * @author Jack Phoenix <jack@countervandalism.net>
 * @copyright Copyright © 2008-2014 Jack Phoenix
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 * @note Uses GPL-licensed code from LoginReg extension (functions
 * fnRegisterAutoAddFriend and fnRegisterTrack)
 */

/**
 * Protect against register_globals vulnerabilities.
 * This line must be present before any global variable is referenced.
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die( "This is not a valid entry point.\n" );
}

// Extension credits that will show up on Special:Version
$wgExtensionCredits['other'][] = array(
	'name' => 'New Signup Page',
	'author' => 'Jack Phoenix',
	'version' => '0.6',
	'url' => 'https://www.mediawiki.org/wiki/Extension:NewSignupPage',
	'description' => 'Adds new features to the [[Special:UserLogin/signup|signup form]]',
);

// ResourceLoader support for MediaWiki 1.17+
$wgResourceModules['ext.newsignuppage'] = array(
	'scripts' => 'NewSignupPage.js',
	'messages' => array(
		'newsignuppage-username-exists',
		'newsignuppage-username-available',
		'newsignuppage-password-mismatch'
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'NewSignupPage'
);

// Internationalization file
$wgMessagesDirs['NewSignupPage'] = __DIR__ . '/i18n';

// Main class file containing all the hooked functions
$wgAutoloadClasses['NewSignupPage'] = __DIR__ . '/NewSignupPage.class.php';

// API module
$wgAutoloadClasses['ApiNewSignupPage'] = __DIR__ . '/ApiNewSignupPage.php';
$wgAPIModules['newsignuppage'] = 'ApiNewSignupPage';

// New user right, allows bypassing the ToS check on signup form
$wgAvailableRights[] = 'bypasstoscheck';

// Hooked functions
$wgHooks['AbortNewAccount'][] = 'NewSignupPage::onAbortNewAccount';
$wgHooks['BeforePageDisplay'][] = 'NewSignupPage::onBeforePageDisplay';
$wgHooks['UserCreateForm'][] = 'NewSignupPage::onSignup';

// Function that conditionally enables some hooks
$wgExtensionFunctions[] = 'NewSignupPage::handleSocialTools';

# Configuration
// Should we track new user registration? Requires that the user_register_track table exists in the DB.
$wgRegisterTrack = false;
// If the new user was referred to the site by an existing user, should we make them friends automatically?
$wgAutoAddFriendOnInvite = false;
// Initialize the extension, even if InviteEmail or UserRelationship classes do
// not exist? Useful for testing.
$wgForceNewSignupPageInitialization = false;