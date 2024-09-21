<?php

use MediaWiki\Auth\AbstractSecondaryAuthenticationProvider;
use MediaWiki\Auth\AuthenticationRequest;
use MediaWiki\Auth\AuthenticationResponse;
use MediaWiki\Auth\AuthManager;
use MediaWiki\MediaWikiServices;

/**
 * @license GPL-2.0-or-later
 * @note Uses GPL-licensed code from LoginReg extension (in beginSecondaryAccountCreation())
 */
class NewSignupPageSecondaryAuthenticationProvider extends AbstractSecondaryAuthenticationProvider {

	/**
	 * @param array $params
	 */
	public function __construct( $params = [] ) {
	}

	/**
	 * Abort the creation of the new account if the user hasn't checked the
	 * "I agree to the terms of service" checkbox and they aren't allowed to
	 * bypass that check.
	 *
	 * @param User $user
	 * @param User $creator
	 * @param array $reqs
	 * @return StatusValue
	 */
	public function testForAccountCreation( $user, $creator, array $reqs ) {
		$req = AuthenticationRequest::getRequestByClass( $reqs, NewSignupPageAuthenticationRequest::class );
		if (
			$req && $req->wpTermsOfService ||
			$creator->isAllowed( 'bypasstoscheck' )
		) {
			return StatusValue::newGood();
		} else {
			return StatusValue::newFatal( 'newsignuppage-must-accept-tos' );
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
		global $wgAutoAddFriendOnInvite, $wgRegisterTrack;

		$req = AuthenticationRequest::getRequestByClass(
			$reqs, NewSignupPageAuthenticationRequest::class
		);

		$referral_user = User::newFromName( $req->referral );
		$user_id_referral = 0;

		if ( $wgAutoAddFriendOnInvite && $referral_user instanceof User ) {
			$user_id_referral = $referral_user->getId();
			if ( $user_id_referral ) {
				// need to create fake request first
				$rel = new UserRelationship( $referral_user );
				$request_id = $rel->addRelationshipRequest( $user, 1, '', false );

				// clear the status
				$rel->updateRelationshipRequestStatus( $request_id, 1 );

				// automatically add relationships
				$rel = new UserRelationship( $user );
				$rel->addRelationship( $request_id, true );

				// Update social statistics for both users (so that we don't
				// show "0 of 0" in the new user's profile when they in fact
				// do have one friend already!)
				// @todo FIXME: broken until UserStatsTrack is refactored to support RequestContext
				// instead of global objects (the global object in incStatField() is _not_
				// our $user even though by all logic it should be and it was in older versions
				// of MW)
				$stats = new UserStatsTrack( $user->getId(), $user->getName() );
				$stats->updateRelationshipCount( 1 );
				$stats->incStatField( 'friend' );

				$statsReferringUser = new UserStatsTrack( $user_id_referral, $referral_user->getName() );
				$statsReferringUser->updateRelationshipCount( 1 );
				$statsReferringUser->incStatField( 'friend' );
			}
		}

		if ( $wgRegisterTrack ) {
			$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
			$cache->delete( $cache->makeKey( 'users', 'new', '1' ) );

			// How the user registered (via email from friend, just on the site etc.)?
			$from = $req->from;
			if ( !$from ) {
				$from = 0;
			}

			// Track if the user clicked on email from friend
			if ( $referral_user instanceof User ) {
				// Update the social statistics of the referring user (to give
				// them points, if specified so on the configuration file)
				$stats = new UserStatsTrack( $referral_user->getId(), $referral_user->getName() );
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
						$user->getUserPage()->getFullURL(),
						$user->getName()
					)->parse();
					$m->addMessage(
						$referral_user,
						UserSystemMessage::TYPE_RECRUIT,
						$message
					);
				}
			}

			// Track registration
			$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
			$dbw->insert(
				'user_register_track',
				[
					'ur_actor' => $user->getActorId(),
					'ur_actor_referral' => ( $referral_user instanceof User ? $referral_user->getActorId() : 0 ),
					'ur_from' => $from,
					'ur_date' => $dbw->timestamp( date( 'Y-m-d H:i:s' ) )
				],
				__METHOD__
			);
		}

		return AuthenticationResponse::newPass();
	}

}
