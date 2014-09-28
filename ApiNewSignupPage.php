<?php
/**
 * NewSignupPage API module -- backend for certain AJAX-y actions done by the
 * JS file
 *
 * @file
 * @ingroup API
 * @date 13 September 2013
 * @see http://www.mediawiki.org/wiki/API:Extensions#ApiSampleApiExtension.php
 */
class ApiNewSignupPage extends ApiBase {

	public function execute() {
		global $wgLang;

		$user = $this->getUser();

		// Get the request parameters
		$params = $this->extractRequestParams();

		wfSuppressWarnings();
		$username = $params['username'];
		wfRestoreWarnings();

		// You only had one job...
		if ( !$username || $username === null ) {
			$this->dieUsageMsg( 'missingparam' );
		}

		$dbr = $this->getDB();

		// Check existence by trying to get the user ID; if we can get it, then
		// there is a user with that name --> else it's good to go
		// ucfirst() call is needed because MediaWiki treats user:ashley and user:Ashley
		// as the same, generally speaking
		$exists = $dbr->selectField(
			'user',
			array( 'user_id' ),
			array( 'user_name' => $wgLang->ucfirst( $username ) ),
			__METHOD__
		);

		$output = array( 'exists' => (bool) $exists );

		// Top level
		$this->getResult()->addValue( null, $this->getModuleName(),
			array( 'result' => $output )
		);

		return true;
	}

	/**
	 * Discourage casual browsing.
	 *
	 * @return Boolean
	 */
	function mustBePosted() {
		return true;
	}

	/**
	 * @return String: human-readable module description
	 */
	public function getDescription() {
		return 'API for checking the existence of a username';
	}

	/**
	 * @return Array
	 */
	public function getAllowedParams() {
		return array(
			'username' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			)
		);
	}

	// Describe the parameter
	public function getParamDescription() {
		return array(
			'username' => 'Username to perform an existence check on'
		);
	}

	// Get examples
	public function getExamples() {
		return array(
			'api.php?action=newsignuppage&username=Foo bar' => 'Check if [[User:Foo bar]] is already registered'
		);
	}
}