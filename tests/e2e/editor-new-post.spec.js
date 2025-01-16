/**
 * External dependencies
 */
import { v4 as uuidv4 } from 'uuid';

/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

test.describe( 'Editor: saving a new post', () => {
	let page, postTitle, postId;

	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();

		const uuid = uuidv4();
		postTitle = `Test Post ${ uuid }`; // Sometimes this runs more than once within a microsecond so it's a UUID.

		// eslint-disable-next-line no-console
		console.log( `New post ${ postTitle }` );

		// Even though we're using WP's npm package, it's more straightforward to do it this way, at least for me in this environment.
		await page.goto( 'http://stream.wpenv.net/wp-admin/post-new.php' );
		await page.getByLabel( 'Add title' ).click();
		await page.getByLabel( 'Add title' ).fill( postTitle );
		await page.getByLabel( 'Add title' ).press( 'Tab' );
		await page.getByLabel( 'Empty block; start writing or' ).fill( 'I\'m a test post' );
		await page.getByRole( 'button', { name: 'Publish', exact: true } ).click();

		postId = await page.locator( 'input#post_ID' ).inputValue();

		// eslint-disable-next-line no-console
		console.log( `Post ID: ${ postId }` );

		// We need to wait for both of the editor responses! The post saves to the posts table then the metadata saves to postmeta.
		await Promise.all( [
			page.waitForResponse( ( resp ) => resp.url().includes( 'meta-box-loader' ) && resp.status() === 302 ),
			page.waitForResponse( ( resp ) => resp.url().includes( `wp-json/wp/v2/posts/${ postId }` ) && resp.status() === 200 ),
			page.getByLabel( 'Editor publish' ).getByRole( 'button', { name: 'Publish', exact: true } ).click(),
		] );

		// They are too much in the posts table so I'm deleting them.
		await page.goto( 'http://stream.wpenv.net/wp-admin/edit.php?post_type=post' );
		const listTable = page.getByRole( 'table', { name: 'Table ordered by' } );
		await expect( listTable ).toBeVisible();

		// Move post to trash.
		await listTable.getByRole( 'link', { name: `“${ postTitle }” (Edit)` } ).hover();
		await listTable.getByRole( 'link', { name: `Move “${ postTitle }” to the Trash` } ).click();

		// Ok, we're all set up, let's go to our page.
		await page.goto( 'http://stream.wpenv.net/wp-admin/admin.php?page=wp_stream' );
	} );

	// Do we have a published row?
	test( 'has published row', async () => {
		// Expects Stream log to have "Test Post" post published visible.
		await expect( page.getByText( `"${ postTitle }" post published` ) ).toBeVisible();
	} );

	// We should not have an updated row. This times out which makes it fail.
	test( 'does not have updated row', async () => {
		// Expects Stream log to have "Test Post" post published visible.
		await expect( page.getByText( `"${ postTitle }" post updated` ) ).not.toBeVisible();
	} );
} );
