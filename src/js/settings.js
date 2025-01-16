/* eslint-disable camelcase */
/**
 * External dependencies
 */
import $ from 'jquery';

let network_affix;
if ( 'wp_stream_network' === $( 'input[name="option_page"]' ).val() ) {
	network_affix = '_network_affix';
} else {
	network_affix = '';
}
const keepRecordsIndefinitely = $( '#wp_stream' + network_affix + '\\[general_keep_records_indefinitely\\]' ),
	keepRecordsFor = $( '#wp_stream' + network_affix + '_general_records_ttl' ),
	keepRecordsForRow = keepRecordsFor.closest( 'tr' );

function toggleKeepRecordsFor() {
	if ( keepRecordsIndefinitely.is( ':checked' ) ) {
		keepRecordsForRow.addClass( 'hidden' );
		keepRecordsFor.addClass( 'hidden' );
	} else {
		keepRecordsForRow.removeClass( 'hidden' );
		keepRecordsFor.removeClass( 'hidden' );
	}
}

keepRecordsIndefinitely.on(
	'change', function() {
		toggleKeepRecordsFor();
	}
);

toggleKeepRecordsFor();

// Confirmation on some important actions
$( '#wp_stream_general_reset_site_settings' ).click(
	function( e ) {
		if ( ! confirm( window[ 'wp-stream-settings' ].i18n.confirm_defaults ) ) { // eslint-disable-line no-alert
			e.preventDefault();
		}
	}
);

// Settings page tabs
const $tabs = $( '.nav-tab-wrapper' ),
	$panels = $( '.nav-tab-content table.form-table' ),
	$activeTab = $tabs.find( '.nav-tab-active' ),
	defaultIndex = $activeTab.length > 0 ? $tabs.find( 'a' ).index( $activeTab ) : 0,
	hashIndexStart = window.location.hash.match( /^#(\d+)$/ ),
	currentHash = ( null !== hashIndexStart ? hashIndexStart[ 1 ] : defaultIndex ),
	syncFormAction = function( index ) {
		const $optionsForm = $( 'input[name="option_page"][value^="wp_stream"]' ).closest( 'form' );
		if ( $optionsForm.length === 0 ) {
			return;
		}
		const currentAction = $optionsForm.attr( 'action' );

		$optionsForm.prop( 'action', currentAction.replace( /(^[^#]*).*$/, '$1#' + index ) );
	};

$tabs.on(
	'click', 'a', function() {
		const index = $tabs.find( 'a' ).index( $( this ) ),
			hashIndex = window.location.hash.match( /^#(\d+)$/ );

		$panels.hide().eq( index ).show();

		$tabs
			.find( 'a' )
			.removeClass( 'nav-tab-active' )
			.filter( $( this ) )
			.addClass( 'nav-tab-active' );

		if ( '' === window.location.hash || null !== hashIndex ) {
			window.location.hash = index;
		}

		syncFormAction( index );

		return false;
	}
);

$tabs.children().eq( currentHash ).trigger( 'click' );
