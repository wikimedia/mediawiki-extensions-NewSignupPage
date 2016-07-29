<?php

use MediaWiki\Auth\AuthManager;
use MediaWiki\Auth\AbstractSecondaryAuthenticationProvider;
use MediaWiki\Auth\AuthenticationRequest;
use MediaWiki\Auth\AuthenticationResponse;

/**
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 * @note Uses GPL-licensed code from LoginReg extension (in beginSecondaryAccountCreation())
 */
class NewSignupPageSecondaryAuthenticationProvider extends AbstractSecondaryAuthenticationProvider {

	/**
	 * @param array $params
	 */
	public function __construct( $params = [] ) {}

	/**
	 * Abort the creation of the new account if the user hasn't checked the
	 * "I agree to the terms of service" checkbox and they aren't allowed to
	 * bypass that check.
	 *
	 * @param User $user
	 * @param $creator
	 * @param array $reqs
	 * @return StatusValue
	 */
	public function testForAccountCreation( $user, $creator, array $reqs ) {
		$req = AuthenticationRequest::getRequestByClass( $reqs, NewSignupPageAuthenticationRequest::class );
		if (
			$req && $req->wpTermsOfService ||
			$creator->isAllowed( 'bypasstoscheck' )
		)
		{
			return StatusValue::newGood();
		} else {
			return StatusValue::newFatal( 'shoutwiki-must-accept-tos' );
		}
	}

	public function getAuthenticationRequests( $action, array $options ) {
		if ( $action === AuthManager::ACTION_CREATE ) {
			return [ new NewSignupPageAuthenticationRequest(
				$this->manager->getRequest()
			) ];
		}

		return [];
	}

	public function beginSecondaryAuthentication( $user, array $reqs ) {
		return AuthenticationResponse::newAbstain();
	}

	public function beginSecondaryAccountCreation( $user, $creator, array $reqs ) {
		global $wgMemc, $wgAutoAddFriendOnInvite, $wgRegisterTrack;

		$req = AuthenticationRequest::getRequestByClass(
			$reqs, NewSignupPageAuthenticationRequest::class
		);

		if ( $wgAutoAddFriendOnInvite ) {
			$referral_user = $req->referral;
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

					// automatically add relationships
					$rel = new UserRelationship( $user->getName() );
					$rel->addRelationship( $request_id, true );

					// Update social statistics for both users (so that we don't
					// show "0 of 0" in the new user's profile when they in fact
					// do have one friend already!)
					// @todo CHECKME: Does this work as intended? Locally I wasn't
					// able to get it working and I figured that's got something
					// to do wit hthe fact that updateRelationshipCount here
					// does an UPDATE query with the LOW_PRIORITY option...
					$stats = new UserStatsTrack( $user->getId(), '' );
					$stats->updateRelationshipCount( 1 );
					$stats->incStatField( 'friend' );

					$statsReferringUser = new UserStatsTrack( $user_id_referral, '' );
					$statsReferringUser->updateRelationshipCount( 1 );
					$statsReferringUser->incStatField( 'friend' );
				}
			}
		}

		if ( $wgRegisterTrack ) {
			$wgMemc->delete( wfMemcKey( 'users', 'new', '1' ) );

			// How the user registered (via email from friend, just on the site etc.)?
			$from = $req->from;
			if ( !$from ) {
				$from = 0;
			}

			// Track if the user clicked on email from friend
			$user_id_referral = 0;
			$user_name_referral = '';
			$referral_user = $req->referral;

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
					$message = wfMessage(
						'newsignuppage-recruited',
						$user_registering_title->getFullURL(),
						$user->getName()
					)->parse();
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
		}

		return AuthenticationResponse::newPass();
	}

}