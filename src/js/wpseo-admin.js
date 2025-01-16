/**
 * External dependencies
 */
import $ from 'jquery';

let highlight, input, tab;

if ( window.location.hash.includes( 'stream-highlight-' ) ) {
	highlight = window.location.hash.replace( 'stream-highlight-', '' );
	input = $( ':input' + highlight );

	window.location.hash = '';

	if ( input.length ) {
		if ( $( '#wpseo-tabs' ).length ) {
			tab = input.parents( '.wpseotab' ).first().attr( 'id' );
			window.location.hash = '#top#' + tab;
		}

		$( document ).ready(
			function() {
				setTimeout(
					function() {
						$( 'body,html' ).animate(
							{ scrollTop: input.offset().top - 50 },
							'slow',
							function() {
								input.animate( { backgroundColor: 'yellow' }, 'slow' );
							}
						);
					}, 500
				);
			}
		);
	}
}
