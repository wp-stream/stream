/**
 * External dependencies
 */
import $ from 'jquery';

/**
 * Internal dependencies
 */
import '../css/alerts-list.scss';

$( '.inline-edit-col-left, .inline-edit-col-right, #major-publishing-actions', '.edit-php.post-type-wp_stream_alerts' ).each(
	function() {
		$( this ).remove();
	}
);

// This is done with JS instead of CSS to override the inline styles added by Select2's JS.
$( '.select2-container', '.inline-edit-col' ).css( { width: '100%' } );
