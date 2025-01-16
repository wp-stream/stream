/**
 * External dependencies
 */
import { test as setup } from '@playwright/test';

const authFile = 'playwright/.auth/user.json';

/**
 * Log in before all the tests.
 * @see https://playwright.dev/docs/auth
 */
setup( 'authenticate', async ( { page } ) => {
	// Log in.
	await page.goto( 'http://stream.wpenv.net/wp-login.php' );
	await page.getByLabel( 'Username or Email Address' ).fill( 'admin' );
	await page.getByLabel( 'Password', { exact: true } ).fill( 'password' );
	await page.getByRole( 'button', { name: 'Log In' } ).click();
	// Wait until the page receives the cookies.

	// Sometimes login flow sets cookies in the process of several redirects.
	// Wait for the final URL to ensure that the cookies are actually set.
	await page.waitForURL( 'http://stream.wpenv.net/wp-admin/' );

	await page.goto( 'http://stream.wpenv.net/wp-admin/network/plugins.php' );
	const isActive = await page.getByLabel( 'Network Deactivate Stream' ).isVisible();

	// eslint-disable-next-line no-console
	console.log( `Stream is currently active: ${ isActive }` );

	if ( isActive ) {
		// eslint-disable-next-line no-console
		console.log( 'Deactivating Stream before tests.' );
		await page.getByLabel( 'Network Deactivate Stream' ).click();
	}

	// End of authentication steps.
	await page.context().storageState( { path: authFile } );

	// eslint-disable-next-line no-console
	console.log( 'Done with network setup.' );
} );
