/* eslint-disable camelcase */
/**
 * External dependencies
 */
import $ from 'jquery';

/**
 * Internal dependencies
 */
import wp_stream_regenerate_alt_rows from './utils/wp-stream-regenerate-alt-rows';

const $excludeRows = $( '.stream-exclude-list tbody tr:not(.hidden)' );
const $placeholderRow = $( '.stream-exclude-list tr.helper' );

const initSettingsSelect2 = function( $rowsWithSelect2 ) {
	let $input_user;

	$( 'select.select2-select.connector_or_context', $rowsWithSelect2 ).each(
		function( k, el ) {
			$( el ).select2(
				{
					allowClear: true,
					templateResult( item ) {
						if ( typeof item.id === 'undefined' ) {
							return item.text;
						}
						if ( item.id.indexOf( '-' ) === -1 ) {
							return $( '<span class="parent">' + item.text + '</span>' );
						}
						return $( '<span class="child">' + item.text + '</span>' );
					},
					matcher( params, data ) {
						const match = $.extend( true, {}, data );

						if ( null === params.term || $.trim( params.term ) === '' ) {
							return match;
						}

						const term = params.term.toLowerCase();

						match.id = match.id.replace( 'blogs', 'sites' );
						if ( match.id.toLowerCase().indexOf( term ) >= 0 ) {
							return match;
						}

						if ( match.children ) {
							for ( let i = match.children.length - 1; i >= 0; i-- ) {
								const child = match.children[ i ];

								// Remove term from results if it doesn't match.
								if ( child.id.toLowerCase().indexOf( term ) === -1 ) {
									match.children.splice( i, 1 );
								}
							}

							if ( match.children.length > 0 ) {
								return match;
							}
						}

						return null;
					},
				}
			).on(
				'change', function() {
					const row = $( this ).closest( 'tr' );
					let connector = $( this ).val();
					if ( connector && 0 < connector.indexOf( '-' ) ) {
						const connector_split = connector.split( '-' );
						connector = connector_split[ 0 ];
					}
					getActions( row, connector );
				}
			);
		}
	);

	$( 'select.select2-select.action', $rowsWithSelect2 ).each(
		function( k, el ) {
			$( el ).select2(
				{
					allowClear: true,
				}
			);
		}
	);

	$( 'select.select2-select.author_or_role', $rowsWithSelect2 ).each(
		function( k, el ) {
			$input_user = $( el );

			$input_user.select2(
				{
					ajax: {
						type: 'POST',
						url: window.ajaxurl,
						dataType: 'json',
						quietMillis: 500,
						data( term, page ) {
							return {
								find: term,
								limit: 10,
								pager: page,
								action: 'stream_get_users',
								nonce: $input_user.data( 'nonce' ),
							};
						},
						processResults( response ) {
							const answer = {
								results: [
									{ text: '', id: '' },
									{ text: 'Roles', children: [] },
									{ text: 'Users', children: [] },
								],
							};

							if ( true !== response.success || undefined === response.data || true !== response.data.status ) {
								return answer;
							}

							if ( undefined === response.data.users || undefined === response.data.roles ) {
								return answer;
							}

							const roles = [];

							$.each(
								response.data.roles, function( id, text ) {
									roles.push(
										{
											id,
											text,
										}
									);
								}
							);

							answer.results[ 1 ].children = roles;
							answer.results[ 2 ].children = response.data.users;

							// Return the value of more so Select2 knows if more results can be loaded
							return answer;
						},
					},
					templateResult( object ) {
						const $result = $( '<div>' ).text( object.text );

						if ( 'undefined' !== typeof object.icon && object.icon ) {
							$result.prepend( $( '<img src="' + object.icon + '" class="wp-stream-select2-icon">' ) );

							// Add more info to the container
							$result.attr( 'title', object.tooltip );
						}

						// Add more info to the container
						if ( 'undefined' !== typeof object.tooltip ) {
							$result.attr( 'title', object.tooltip );
						} else if ( 'undefined' !== typeof object.user_count ) {
							$result.attr( 'title', object.user_count );
						}

						return $result;
					},
					templateSelection( object ) {
						const $result = $( '<div>' ).text( object.text );

						if ( $.isNumeric( object.id ) && object.text.indexOf( 'icon-users' ) < 0 ) {
							$result.append( $( '<i class="icon16 icon-users"></i>' ) );
						}

						return $result;
					},
					allowClear: true,
					placeholder: $input_user.data( 'placeholder' ),
				}
			).on(
				'change', function() {
					const value = $( this ).select2( 'data' );

					$( this ).data( 'selected-id', value.id );
					$( this ).data( 'selected-text', value.text );
				}
			);
		}
	);

	$( 'select.select2-select.ip_address', $rowsWithSelect2 ).each(
		function( k, el ) {
			const $input_ip = $( el );
			let searchTerm = '';

			$input_ip.select2(
				{
					ajax: {
						type: 'POST',
						url: window.ajaxurl,
						dataType: 'json',
						quietMillis: 500,
						data( term ) {
							searchTerm = term.term;
							return {
								find: term,
								limit: 10,
								action: 'stream_get_ips',
								nonce: $input_ip.data( 'nonce' ),
							};
						},
						processResults( response ) {
							const answer = { results: [] };
							let ip_chunks = [];

							if ( true === response.success && undefined !== response.data ) {
								$.each(
									response.data, function( key, ip ) {
										answer.results.push(
											{
												id: ip,
												text: ip,
											}
										);
									}
								);
							}

							if ( undefined === searchTerm ) {
								return answer;
							}

							ip_chunks = searchTerm.match( /^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/ );

							if ( null === ip_chunks ) {
								return answer;
							}

							// remove whole match
							ip_chunks.shift();

							ip_chunks = $.grep(
								ip_chunks,
								function( chunk ) {
									const numeric = parseInt( chunk, 10 );
									return numeric <= 255 && numeric.toString() === chunk;
								}
							);

							if ( ip_chunks.length >= 4 ) {
								answer.results.push(
									{
										id: searchTerm,
										text: searchTerm,
									}
								);
							}

							return answer;
						},
					},
					allowClear: false,
					multiple: true,
					maximumSelectionSize: 1,
					placeholder: $input_ip.data( 'placeholder' ),
					tags: true,
				}
			);
		}
	).on(
		'change', function() {
			$( this ).prev( '.select2-container' ).find( 'input.select2-input' ).blur();
		}
	);

	$( 'ul.select2-choices, ul.select2-choices li, input.select2-input', '.stream-exclude-list tr:not(.hidden) .ip_address' ).on(
		'mousedown click focus', function() {
			const $container = $( this ).closest( '.select2-container' ),
				$input = $container.find( 'input.select2-input' ),
				value = $container.select2( 'data' );

			if ( value.length >= 1 ) {
				$input.blur();
				return false;
			}
		}
	);

	$( '.exclude_rules_remove_rule_row', $rowsWithSelect2 ).on(
		'click', function( e ) {
			const $thisRow = $( this ).closest( 'tr' );

			$thisRow.remove();

			recalculate_rules_found();
			recalculate_rules_selected();

			e.preventDefault();
		}
	);
};

initSettingsSelect2( $excludeRows );

$( 'select.select2-select.author_or_role', $excludeRows ).each(
	function() {
		const $option = $( '<option selected>' + $( this ).data( 'selected-text' ) + '</option>' ).val( $( this ).data( 'selected-id' ) );
		$( this ).append( $option ).trigger( 'change' );
	}
);

$( 'select.select2-select.connector_or_context', $excludeRows ).each(
	function() {
		const parts = [
			$( this ).siblings( '.connector' ).val(),
			$( this ).siblings( '.context' ).val(),
		];
		if ( parts[ 1 ] === '' ) {
			parts.splice( 1, 1 );
		}
		$( this ).val( parts.join( '-' ) ).trigger( 'change' );
	}
);

$( '#exclude_rules_new_rule' ).on(
	'click', function() {
		const $newRow = $placeholderRow.clone();

		$newRow.removeAttr( 'class' );
		$newRow.insertBefore( $placeholderRow );

		initSettingsSelect2( $newRow );
		recalculate_rules_found();
		recalculate_rules_selected();
	}
);

$( '#exclude_rules_remove_rules' ).on(
	'click', function() {
		const $excludeList = $( 'table.stream-exclude-list' ),
			selectedRows = $( 'tbody input.cb-select:checked', $excludeList ).closest( 'tr' );

		if ( ( $( 'tbody tr', $excludeList ).length - selectedRows.length ) >= 2 ) {
			selectedRows.remove();
		} else {
			$( ':input', selectedRows ).val( '' );
			$( selectedRows ).not( ':first' ).remove();
			$( '.select2-select', selectedRows ).select2( 'val', '' );
		}

		$excludeList.find( 'input.cb-select' ).prop( 'checked', false );

		recalculate_rules_found();
		recalculate_rules_selected();
	}
);

$( '.stream-exclude-list' ).closest( 'form' ).submit(
	function() {
		$( '.stream-exclude-list tbody tr.hidden', this ).each(
			function() {
				$( this ).find( ':input' ).removeAttr( 'name' );
			}
		);
		$( '.stream-exclude-list tbody tr:not(.hidden) select.select2-select.connector_or_context', this ).each(
			function() {
				const parts = $( this ).val().split( '-' );
				$( this ).siblings( '.connector' ).val( parts[ 0 ] );
				$( this ).siblings( '.context' ).val( parts.slice( 1 ).join( '-' ) );
				$( this ).removeAttr( 'name' );
			}
		);
		$( '.stream-exclude-list tbody tr:not(.hidden) select.select2-select.ip_address', this ).each(
			function() {
				const firstSelected = $( 'option:selected', this ).first();

				// Ugly hack to ensure we always pass an empty value or the order of rows gets messed up.
				if ( ! firstSelected.length ) {
					$( this ).append( '<option selected="selected"></option>' );
				}

				$( 'option:selected:not(:first)', this ).each(
					function() {
						firstSelected.attr( 'value', firstSelected.attr( 'value' ) + ',' + $( this ).attr( 'value' ) );
						$( this ).removeAttr( 'selected' );
					}
				);
			}
		);
	}
);

$( '.stream-exclude-list' ).closest( 'td' ).prev( 'th' ).hide();

$( 'table.stream-exclude-list' ).on(
	'click', 'input.cb-select', function() {
		recalculate_rules_selected();
	}
);

function getActions( row, connector ) {
	const trigger_action = $( '.select2-select.action', row ),
		action_value = trigger_action.val();

	trigger_action.empty();
	trigger_action.prop( 'disabled', true );

	const placeholder = $( '<option/>', { value: '', text: '' } );
	trigger_action.append( placeholder );

	const data = {
		action: 'get_actions',
		connector,
	};

	$.post(
		window.ajaxurl, data, function( response ) {
			const success = response.success,
				actions = response.data;
			if ( ! success ) {
				return;
			}
			for ( const key in actions ) {
				if ( actions.hasOwnProperty( key ) ) {
					const value = actions[ key ];
					const option = $( '<option/>', { value: key, text: value } );
					trigger_action.append( option );
				}
			}
			trigger_action.val( action_value );
			trigger_action.prop( 'disabled', false );
			$( document ).trigger( 'alert-actions-updated' );
		}
	);
}

function recalculate_rules_selected() {
	const $selectedRows = $( 'table.stream-exclude-list tbody tr:not( .hidden ) input.cb-select:checked' ),
		$deleteButton = $( '#exclude_rules_remove_rules' );

	if ( 0 === $selectedRows.length ) {
		$deleteButton.prop( 'disabled', true );
	} else {
		$deleteButton.prop( 'disabled', false );
	}
}

function recalculate_rules_found() {
	const $allRows = $( 'table.stream-exclude-list tbody tr:not( .hidden )' ),
		$noRulesFound = $( 'table.stream-exclude-list tbody tr.no-items' ),
		$selectAll = $( '.check-column.manage-column input.cb-select' ),
		$deleteButton = $( '#exclude_rules_remove_rules' );

	if ( 0 === $allRows.length ) {
		$noRulesFound.show();
		$selectAll.prop( 'disabled', true );
		$deleteButton.prop( 'disabled', true );
	} else {
		$noRulesFound.hide();
		$selectAll.prop( 'disabled', false );
	}

	wp_stream_regenerate_alt_rows( $allRows );
}

$( document ).ready(
	function() {
		recalculate_rules_found();
		recalculate_rules_selected();
	}
);
