/**
 * External dependencies
 */
import $ from 'jquery';

// Regenerate alternating row classes
export default function regenerateAltRows( $rows ) {
	if ( ! $rows.length ) {
		return false;
	}

	$rows.removeClass( 'alternate' );

	$rows.each(
		function( index ) {
			$( this ).addClass( index % 2 ? '' : 'alternate' );
		}
	);
}
