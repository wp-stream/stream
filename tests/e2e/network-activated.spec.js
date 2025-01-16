/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

let page;

test.describe.configure( { mode: 'serial' } );

test.beforeAll( async ( { browser } ) => {
	page = await browser.newPage();
} );

test.afterAll( async () => {
	await page.goto( 'http://stream.wpenv.net/wp-admin/network/plugins.php' );
	const isActive = await page
		.getByLabel( 'Network Deactivate Stream' )
		.isVisible();

	if ( isActive ) {
		// eslint-disable-next-line no-console
		console.log( 'Deactivating Stream after Network tests.' );
		await page.getByLabel( 'Network Deactivate Stream' ).click();
	}
} );

test.describe( 'Network: shows network activated states', () => {
	// Do we have a published row?
	test( 'does not show stream in network admin when deactivated', async () => {
		await page.goto( 'http://stream.wpenv.net/wp-admin/network/index.php' );
		// Expects Stream log to not have the Network Settings.
		await expect(
			page.locator( '[href*="admin.php?page=wp_stream"]' ),
		).not.toBeVisible();
	} );

	// We should not have an updated row. This times out which makes it fail.
	test( 'does not have updated row', async () => {
		await page.goto( 'http://stream.wpenv.net/wp-admin/network/plugins.php' );
		const isInactive = await page
			.getByLabel( 'Network Activate Stream' )
			.isVisible();

		if ( isInactive ) {
			// eslint-disable-next-line no-console
			console.log( 'Activating Stream for next tests.' );
			await page.getByLabel( 'Network Activate Stream' ).click();
		}

		await page.goto( 'http://stream.wpenv.net/wp-admin/network/index.php' );
		// Expects Stream log to not have the Network Settings.
		await expect(
			page.locator( '[href*="admin.php?page=wp_stream_network_settings"]' ),
		).toBeVisible();
	} );
} );
