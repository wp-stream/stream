/* eslint-disable camelcase */
/**
 * External dependencies
 */
import $ from 'jquery';

/**
 * Internal dependencies
 */
import wp_stream_regenerate_alt_rows from './utils/wp-stream-regenerate-alt-rows';

$( document ).ready(
	function() {
		// Only run on wp_stream when page is 1 and the order is desc
		if ( 'toplevel_page_wp_stream' !== window[ 'wp-stream-live-updates' ].current_screen || '1' !== window[ 'wp-stream-live-updates' ].current_page || 'asc' === window[ 'wp-stream-live-updates' ].current_order ) {
			return;
		}

		// Do not run if there are filters in use
		if ( parseInt( window[ 'wp-stream-live-updates' ].current_query_count, 10 ) > 1 ) {
			return;
		}

		const list_sel = '.toplevel_page_wp_stream #the-list';

		// Set initial beat to fast. WP is designed to slow this to 15 seconds after 2.5 minutes.
		wp.heartbeat.interval( 'fast' );

		$( document ).on(
			'heartbeat-send.stream', function( e, data ) {
				data[ 'wp-stream-heartbeat' ] = 'live-update';

				const last_item = $( list_sel + ' tr:first .column-date time' );
				let last_time = 1;

				if ( 0 !== last_item.length ) {
					last_time = ( '' === last_item.attr( 'datetime' ) ) ? 1 : last_item.attr( 'datetime' );
				}

				data[ 'wp-stream-heartbeat-last-time' ] = last_time;
				data[ 'wp-stream-heartbeat-query' ] = window[ 'wp-stream-live-updates' ].current_query;
			}
		);

		// Listen for "heartbeat-tick" on $(document).
		$( document ).on(
			'heartbeat-tick.stream', function( e, data ) {
				// If this no rows return then we kill the script
				if ( ! data[ 'wp-stream-heartbeat' ] || 0 === data[ 'wp-stream-heartbeat' ].length ) {
					return;
				}

				const show_on_screen = $( '#edit_stream_per_page' ).val(),
					$current_items = $( list_sel + ' tr' ),
					$new_items = $( data[ 'wp-stream-heartbeat' ] );

				// Remove all default classes and add class to highlight new rows
				$new_items.addClass( 'new-row' );

				// Check if first tr has the alternate class
				const has_class = ( $current_items.first().hasClass( 'alternate' ) );

				// Apply the good class to the list
				if ( 1 === $new_items.length && ! has_class ) {
					$new_items.addClass( 'alternate' );
				} else {
					const even_or_odd = ( 0 === $new_items.length % 2 && ! has_class ) ? 'even' : 'odd';
					// Add class to nth child because there is more than one element
					$new_items.filter( ':nth-child(' + even_or_odd + ')' ).addClass( 'alternate' );
				}

				// Add element to the dom
				$( list_sel ).prepend( $new_items );

				$( '.metabox-prefs input' ).each(
					function() {
						if ( true !== $( this ).prop( 'checked' ) ) {
							const label = $( this ).val();
							$( 'td.column-' + label ).hide();
						}
					}
				);

				// Remove the number of element added to the end of the list table
				const slice_rows = show_on_screen - ( $new_items.length + $current_items.length );

				if ( slice_rows < 0 ) {
					$( list_sel + ' tr' ).slice( slice_rows ).remove();
				}

				// Remove the no items row
				$( list_sel + ' tr.no-items' ).remove();

				// Update pagination
				const total_items_i18n = data.total_items_i18n || '';

				if ( total_items_i18n ) {
					$( '.displaying-num' ).text( total_items_i18n );
					$( '.total-pages' ).text( data.total_pages_i18n );
					$( '.tablenav-pages' ).find( '.next-page, .last-page' ).toggleClass( 'disabled', data.total_pages === $( '.current-page' ).val() );
					$( '.tablenav-pages .last-page' ).attr( 'href', data.last_page_link );
				}

				// Allow others to hook in, ie: timeago
				$( list_sel ).parent().trigger( 'updated' );

				// Regenerate alternating row classes
				wp_stream_regenerate_alt_rows( $( list_sel + ' tr' ) );

				// Remove background after a certain amount of time
				setTimeout(
					function() {
						$( '.new-row' ).addClass( 'fadeout' );
						setTimeout(
							function() {
								$( list_sel + ' tr' ).removeClass( 'new-row fadeout' );
							}, 500
						);
					}, 3000
				);
			}
		);
	}
);
