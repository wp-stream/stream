/* eslint-disable camelcase */
/**
 * External dependencies
 */
import $ from 'jquery';

// List table actions, ignores filtering
$( '.actions :submit:not([name="filter_action"])' ).on(
	'click', function( e ) {
		if ( $( 'table.widefat tbody :checkbox:checked' ).length > window[ 'wp-stream-global' ].bulk_actions.threshold ) {
			warning_message( e );
		}
	}
);

// Post type empty trash
$( '#delete_all' ).on(
	'click', function( e ) {
		const trash_count = parseInt( $( 'ul.subsubsub li.trash .count' ).text().replace( /\D/g, '' ), 10 );

		if ( trash_count > window[ 'wp-stream-global' ].bulk_actions.threshold ) {
			warning_message( e );
		}
	}
);

function warning_message( e ) {
	if ( ! window.confirm( window[ 'wp-stream-global' ].bulk_actions.i18n.confirm_action ) ) { // eslint-disable-line no-alert
		e.preventDefault();
	}
}
