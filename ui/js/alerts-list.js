/* globals jQuery */
( function( $ ) {
	$( document ).ready(
		function() {
			$( '.inline-edit-col-left, .inline-edit-col-right, #major-publishing-actions', '.edit-php.post-type-wp_stream_alerts' ).each(
				function() {
					$( this ).remove();
				}
			);

			// This is done with JS instead of CSS to override the inline styles added by Select2's JS.
			$( '.select2-container', '.inline-edit-col' ).css( { width: '100%' } );

			// Re-enable the select all functionality
			$( '.wp-list-table thead .check-column input[type="checkbox"]' ).on(
				'click',
				function() {
					$( this )
						.parents( '.wp-list-table' )
						.find( 'tbody .check-column input[type="checkbox"]' )
						.click();
				}
			);
		}
	);
}( jQuery ) );
