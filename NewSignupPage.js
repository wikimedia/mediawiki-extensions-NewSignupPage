/**
 * JavaScript file for performing some interactive and client-side validation of
 * the fields on the registration form to avoid some user frustration, especially
 * on mobile devices
 *
 * @file
 * @date 13 September 2013
 * @author Jack Phoenix
 */
var NewSignupPage = {

	checkUsernameExistence: function() {
		jQuery.post(
			mw.util.wikiScript( 'api' ), {
				action: 'newsignuppage',
				username: jQuery( 'input#wpName2' ).val(),
				format: 'json'
			},
			function( data ) {
				var style, message;

				// Remove previous messages from the DOM, if any, so that we
				// don't show absolutely silly stuff like "Username exists!Username is available!"
				// to the end-user
				if ( jQuery( '#existence-check-result' ).length > 0 ) {
					jQuery( '#existence-check-result' ).remove();
				}

				if ( data.newsignuppage.result.exists === true ) {
					message = mw.msg( 'newsignuppage-username-exists' );
					style = 'padding: 2px; color: red';
				} else {
					// Great success!
					message = mw.msg( 'newsignuppage-username-available' );
					style = 'padding: 2px; color: LimeGreen'; // original one was #6BEC4B
				}

				// This element contains the result of the existence check (gee,
				// who could've thought of that? ;)
				jQuery( 'input#wpName2' ).parent().append(
					'<span id="existence-check-result" style="' + style + '">' +
					message + '</span>'
				);
			}
		);
	}

};

jQuery( document ).ready( function() {
	// Username existence checker
	jQuery( 'input#wpName2' ).on( 'change', function() {
		NewSignupPage.checkUsernameExistence();
	} );

	// Password match checker
	jQuery( 'input#wpPassword2, input#wpRetype' ).on( 'change', function() {
		if ( jQuery( 'span#password-match-check-result' ).length > 0 ) {
			jQuery( 'span#password-match-check-result' ).remove();
		}
		// If both the password and the "type password again" fields have
		// content, but the contents do not match, complain to the user about it
		if (
			jQuery( 'input#wpPassword2' ).val().length > 0 &&
			jQuery( 'input#wpRetype' ).val().length > 0 &&
			!( jQuery( 'input#wpPassword2' ).val() === jQuery( 'input#wpRetype' ).val() )
		)
		{
			var message = mw.msg( 'newsignuppage-password-mismatch' );
			jQuery( 'input#wpRetype' ).parent().append(
				'<span id="password-match-check-result" style="padding: 2px; color: red">' +
				message + '</span>'
			);
		}
		// @todo FIXME: whine if there is no password, maybe?
	} );
} );