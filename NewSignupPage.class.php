<?php
/**
 * NewSignupPage extension for MediaWiki -- enhances the default signup form
 *
 * All class methods are public and static.
 *
 * @file
 * @ingroup Extensions
 * @author Jack Phoenix <jack@countervandalism.net>
 * @copyright Copyright © 2008-2015 Jack Phoenix
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 * @note Uses GPL-licensed code from LoginReg extension (functions
 * fnRegisterAutoAddFriend and fnRegisterTrack)
 */
class NewSignupPage {

	/**
	 * Checks if InviteContacts extension and social tools' core are both loaded
	 * and enables two hooked functions if so
	 */
	public static function handleSocialTools() {
		global $wgForceNewSignupPageInitialization;
		if (
			class_exists( 'InviteEmail' ) && class_exists( 'UserRelationship' ) ||
			$wgForceNewSignupPageInitialization
		)
		{
			global $wgHooks;
			$wgHooks['AddNewAccount'][] = 'NewSignupPage::trackRegistration';
			$wgHooks['AddNewAccount'][] = 'NewSignupPage::autoAddFriend';
		}
	}

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

		// Only do our magic if we're on the login page
		if ( $title->isSpecial( 'Userlogin' ) ) {
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

	/**
	 * Adds the checkbox into Special:UserLogin/signup
	 *
	 * @param QuickTemplate $template QuickTemplate instance
	 * @return bool
	 */
	public static function onSignup( &$template ) {
		global $wgRequest;

		// Terms of Service box
		$template->addInputItem( 'wpTermsOfService', ''/*do *not* have this checked by default!*/, 'checkbox', 'shoutwiki-loginform-tos' );

		// Referrer stuff for social wikis
		$template->addInputItem( 'from', $wgRequest->getInt( 'from' ), 'hidden', '' );
		$template->addInputItem( 'referral', $wgRequest->getVal( 'referral' ), 'hidden', '' );

		return true;
	}

	/**
	 * Abort the creation of the new account if the user hasn't checked the checkbox
	 *
	 * @param User $user The User about to be created (read-only, incomplete)
	 * @param string $message Error message to be displayed to the user, if any
	 * @return bool False by default, true if user has checked the checkbox or has 'bypasstoscheck' right
	 */
	public static function onAbortNewAccount( $user, $message ) {
		global $wgRequest, $wgUser;

		if (
			$wgRequest->getCheck( 'wpTermsOfService' ) ||
			$wgUser->isAllowed( 'bypasstoscheck' )
		)
		{
			return true;
		} else {
			$message = wfMsg( 'shoutwiki-must-accept-tos' );
			return false;
		}

		return false; // since the checkbox isn't checked by default either
	}

	/**
	 * Automatically make the referring user and the newly-registered user
	 * friends if $wgAutoAddFriendOnInvite is set to true.
	 *
	 * @param User $user The newly-created user
	 * @return bool
	 */
	public static function autoAddFriend( $user ) {
		global $wgRequest, $wgAutoAddFriendOnInvite;

		if ( $wgAutoAddFriendOnInvite ) {
			$referral_user = $wgRequest->getVal( 'referral' );
			if ( $referral_user ) {
				$user_id_referral = User::idFromName( $referral_user );
				if ( $user_id_referral ) {
					// need to create fake request first
					$rel = new UserRelationship( $referral_user );
					$request_id = $rel->addRelationshipRequest(
						$user->getName(), 1, '', false
					);

					// clear the status
					$rel->updateRelationshipRequestStatus( $request_id, 1 );

					// automatically add relationhips
					$rel = new UserRelationship( $user->getName() );
					$rel->addRelationship( $request_id, true );
				}
			}
		}

		return true;
	}

	/**
	 * Track new user registrations to the user_register_track database table if
	 * $wgRegisterTrack is set to true.
	 *
	 * @param User $user The newly-created user
	 * @return bool
	 */
	public static function trackRegistration( $user ) {
		global $wgRequest, $wgRegisterTrack, $wgMemc;

		if ( $wgRegisterTrack ) {
			$wgMemc->delete( wfMemcKey( 'users', 'new', '1' ) );

			// How the user registered (via email from friend, just on the site etc.)?
			$from = $wgRequest->getInt( 'from' );
			if ( !$from ) {
				$from = 0;
			}

			// Track if the user clicked on email from friend
			$user_id_referral = 0;
			$user_name_referral = '';
			$referral_user = $wgRequest->getVal( 'referral' );

			if ( $referral_user ) {
				$user_registering_title = Title::makeTitle( NS_USER, $user->getName() );
				$user_title = Title::newFromDBkey( $referral_user );
				$user_id_referral = User::idFromName( $user_title->getText() );
				if ( $user_id_referral ) {
					$user_name_referral = $user_title->getText();
				}

				// Update the social statistics of the referring user (to give
				// them points, if specified so on the configuration file)
				$stats = new UserStatsTrack( $user_id_referral, $user_title->getText() );
				$stats->incStatField( 'referral_complete' );

				// Add a new site activity event that will show up on the output
				// of <siteactivity /> at least
				if ( class_exists( 'UserSystemMessage' ) ) {
					$m = new UserSystemMessage();
					// Nees to be forContent because addMessage adds this into a
					// database table - we don't want to display Japanese text
					// to English users
					$message = wfMsgExt(
						'newsignuppage-recruited',
						array( 'parseinline' ),
						$user_registering_title->getFullURL(),
						$user->getName()
					);
					$m->addMessage( $user_title->getText(), 1, $message );
				}
			}

			// Track registration
			$dbw = wfGetDB( DB_MASTER );
			$dbw->insert(
				'user_register_track',
				array(
					'ur_user_id' => $user->getId(),
					'ur_user_name' => $user->getName(),
					'ur_user_id_referral' => $user_id_referral,
					'ur_user_name_referral' => $user_name_referral,
					'ur_from' => $from,
					'ur_date' => date( 'Y-m-d H:i:s' )
				),
				__METHOD__
			);
			$dbw->commit(); // Just in case...
		}

		return true;
	}

}