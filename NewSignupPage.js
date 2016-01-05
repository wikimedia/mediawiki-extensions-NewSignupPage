/**
 * JavaScript file for performing some interactive and client-side validation of
 * the password fields on the registration form to avoid some user frustration,
 * especially on mobile devices.
 *
 * @file
 * @date 5 January 2016
 * @author Jack Phoenix
 */
$( function() {
	// Password match checker
	$( 'input#wpPassword2, input#wpRetype' ).on( 'change', function() {
		if ( $( 'span#password-match-check-result' ).length > 0 ) {
			$( 'span#password-match-check-result' ).remove();
		}
		// If both the password and the "type password again" fields have
		// content, but the contents do not match, complain to the user about it
		if (
			$( 'input#wpPassword2' ).val().length > 0 &&
			$( 'input#wpRetype' ).val().length > 0 &&
			!( $( 'input#wpPassword2' ).val() === $( 'input#wpRetype' ).val() )
		)
		{
			var message = mw.msg( 'newsignuppage-password-mismatch' );
			$( 'input#wpRetype' ).parent().append(
				'<span id="password-match-check-result" style="padding: 2px; color: red">' +
				message + '</span>'
			);
		}
		// @todo FIXME: whine if there is no password, maybe?
	} );
} );