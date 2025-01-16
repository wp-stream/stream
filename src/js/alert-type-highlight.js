/**
 * External dependencies
 */
import $ from 'jquery';

$( document ).ready(
	function() {
		/**
		 * Remove highlights on Record list screen.
		 */
		$( '.alert-highlight .action-link[href="#"]' ).each(
			function() {
				const actionLink = $( this );

				/**
				 * Ajax call to remove the highlight.
				 *
				 * @return void
				 */
				actionLink.click(
					function( e ) {
						let recordId;
						e.preventDefault();
						recordId = actionLink.parents( '.alert-highlight' ).attr( 'class' ).match( /record\-id\-[\w-]*\b/ );
						recordId = recordId[ 0 ].replace( 'record-id-', '' );

						const data = {
							action: window[ 'wp-stream-alert-type-highlight' ].removeAction,
							security: window[ 'wp-stream-alert-type-highlight' ].security,
							recordId,
						};

						$.post(
							window[ 'wp-stream-alert-type-highlight' ].ajaxUrl, data, function( response ) {
								if ( true === response.success ) {
									ajaxDone();
								}
							}
						);

						/**
						 * Fires when Ajax complete.
						 */
						function ajaxDone() {
							const row = actionLink.parents( '.alert-highlight' ),
								odd = $( '.striped > tbody > :nth-child( odd )' );
							if ( row.is( odd ) ) {
								row.animate(
									{ backgroundColor: '#f9f9f9' }, 300, function() {
										row.removeClass( 'alert-highlight' );
									}
								);
							} else {
								row.animate(
									{ backgroundColor: '' }, 300, function() {
										row.removeClass( 'alert-highlight' );
									}
								);
							}
							actionLink.remove();
						}
					}
				);
			}
		);
	}
);
