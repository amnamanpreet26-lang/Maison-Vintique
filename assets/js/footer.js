( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var form = document.getElementById( 'mv-newsletter-form' );
		if ( ! form ) {
			return;
		}

		var input   = form.querySelector( '#mv-newsletter-email' );
		var button  = form.querySelector( '.mv-newsletter-form__submit' );
		var msg     = form.querySelector( '.mv-newsletter-form__msg' );
		var nonceEl = form.querySelector( '#mve_newsletter_nonce' );

		function setMessage( text, state ) {
			msg.textContent = text;
			msg.setAttribute( 'data-state', state || '' );
		}

		function isValidEmail( value ) {
			return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( value );
		}

		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();

			var email = input.value.trim();

			if ( ! isValidEmail( email ) ) {
				setMessage( window.mveNewsletter ? mveNewsletter.i18n.invalidEmail : 'Please enter a valid email address.', 'error' );
				input.focus();
				return;
			}

			if ( ! window.mveNewsletter ) {
				setMessage( 'Newsletter service is unavailable right now.', 'error' );
				return;
			}

			form.classList.add( 'is-loading' );
			button.disabled = true;
			setMessage( '' );

			var body = new URLSearchParams();
			body.set( 'action', 'mv_newsletter_subscribe' );
			body.set( 'email', email );
			body.set( 'nonce', nonceEl ? nonceEl.value : '' );

			fetch( mveNewsletter.ajaxUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: body.toString(),
				credentials: 'same-origin'
			} )
				.then( function ( response ) {
					return response.json();
				} )
				.then( function ( json ) {
					if ( json && json.success ) {
						setMessage( mveNewsletter.i18n.success, 'success' );
						form.reset();
					} else {
						var code = json && json.data && json.data.code;
						var text = mveNewsletter.i18n.genericError;
						if ( 'already_subscribed' === code ) {
							text = mveNewsletter.i18n.alreadySubbed;
						} else if ( 'invalid_email' === code ) {
							text = mveNewsletter.i18n.invalidEmail;
						}
						setMessage( text, 'error' );
					}
				} )
				.catch( function () {
					setMessage( mveNewsletter.i18n.genericError, 'error' );
				} )
				.finally( function () {
					form.classList.remove( 'is-loading' );
					button.disabled = false;
				} );
		} );
	} );
} )();