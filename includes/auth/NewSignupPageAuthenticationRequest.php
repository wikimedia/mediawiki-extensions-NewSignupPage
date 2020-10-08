<?php

use MediaWiki\Auth\AuthenticationRequest;

/**
 * @ingroup Auth
 * @since MediaWiki 1.27
 */
class NewSignupPageAuthenticationRequest extends AuthenticationRequest {
	public $required = self::REQUIRED; // only ToS check is mandatory

	/**
	 * @var int $from Email invitation source identifier to be stored in the
	 * user_email_track table
	 * @see /extensions/MiniInvite/includes/UserEmailTrack.class.php for details
	 */
	public $from;

	/**
	 * @var string|int Username of the person who referred the user creating an
	 * account to the wiki; used to give out points to the referring user and
	 * also automatically friend them and the new user if that configuration
	 * setting is enabled
	 */
	public $referral;

	/**
	 * @var bool $wpTermsOfService Was the "I agree to the terms of service"
	 * checkbox checked? It must be in order for the account creation process
	 * to continue.
	 */
	public $wpTermsOfService;

	/**
	 * @param WebRequest $request
	 */
	public function __construct( $request ) {
		$this->request = $request;
	}

	public function getFieldInfo() {
		global $wgNewSignupPageToSURL, $wgNewSignupPagePPURL;
		return [
			'from' => [
				'type' => 'hidden',
				'optional' => true,
				'value' => $this->request->getInt( 'from' )
			],
			'referral' => [
				'type' => 'hidden',
				'optional' => true,
				'value' => $this->request->getVal( 'referral' )
			],
			'wpTermsOfService' => [
				'type' => 'checkbox',
				'label' => wfMessage(
					'newsignuppage-loginform-tos',
					$wgNewSignupPageToSURL,
					$wgNewSignupPagePPURL
				)
			]
		];
	}

	public function loadFromSubmission( array $data ) {
		// We always want to use this request, so ignore parent's return value.
		parent::loadFromSubmission( $data );

		return true;
	}
}
