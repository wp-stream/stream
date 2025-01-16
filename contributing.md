# Contribute to Stream


## Development Environment

Stream uses [npm](https://npmjs.com) for javascript dependencies, [Composer](https://getcomposer.org) for PHP dependencies and the [Grunt](https://gruntjs.com) task runner to minimize and compile scripts and styles and to deploy to the WordPress.org plugin repository.

Included is a local development environment built with [Docker](https://www.docker.com).

### Requirements

- [Node.js](https://nodejs.org)
- [Composer](https://getcomposer.org)

We suggest using the [Homebrew package manager](https://brew.sh) on macOS to install the dependencies:

	brew install node@20 composer
	brew install --cask docker

### Environment Setup

1. See the [Git Flow](#git-flow) section below for how to fork the repository.
2. Run `npm install` and `composer install` to setup all project dependencies.
3. Run `npm build` to build the assets.
3. Run `npm start` to start the development environment.
4. Run `npm run install-wordpress` to set up the WordPress multisite network.
5. Visit [stream.wpenv.net](http://stream.wpenv.net) and login using `admin` / `password`.
6. Activate the Stream plugin.

### PHP Xdebug

The WordPress container includes the [Xdebug PHP extension](https://xdebug.org). It is configured in the [`php.ini`](./local/docker/wordpress/php.ini) file to work in the [develop, debug and coverage modes](https://xdebug.org/docs/step_debug#mode).

[Step Debugging](https://xdebug.org/docs/step_debug) should work out of the box in VSCode thanks to the configuration file, [`.vscode/launch.json`](.vscode/launch.json). It contains the directory mapping from the WordPress container to the project directory in your code editor.

In order to set up Step Debugging in PhpStorm, follow the [official guide](https://www.jetbrains.com/help/phpstorm/configuring-xdebug.html). Make sure to set up the same directory mappings as defined for VSCode in [`.vscode/launch.json`](.vscode/launch.json), e.g.:
- `${workspaceRoot}` -> `/var/www/html/wp-content/plugins/stream-src`,
- `${workspaceRoot}/build` -> `/var/www/html/wp-content/plugins/stream`,
- `${workspaceRoot}/local/public` -> `/var/www/html`

### Mail Catcher

We use a [MailHog](https://github.com/mailhog/MailHog) container to capture all emails sent by the WordPress container, available at [stream.wpenv.net:8025](https://stream.wpenv.net:8025).

### phpMyAdmin

[phpMyAdmin ](https://www.phpmyadmin.net/) is available at [stream.wpenv.net:8080](http://stream.wpenv.net:8080/).

### Scripts and Commands

We use npm as the canonical task runner for the project. The following commands are available:

- `npm run start` to start the project's Docker containers.
- `npm run stop` to stop the project's Docker containers.
- `npm run stop-all` to stop _all_ Docker containers.
- `npm run build` to build the plugin JS and CSS files.
- `npm run dev` to watch and build the plugin assets continuously.
- `npm run lint` to check JS and PHP files for syntax and style issues.
- `npm run deploy` to deploy the plugin to the WordPress.org repository.
- `npm run cli -- wp info` where `wp info` is the CLI command to run inside the WordPress container. For example, use `npm run cli -- ls -lah` to list all files in the root of the WordPress installation.
- `npm run test` to run PHPunit tests inside the WordPress container.
- `npm run test-xdebug` will run the PHPunit tests with Xdebug enabled.
- `npm run test-e2e` will run the Playwright E2E tests.
- `npm run test-e2e-debug` will run the Playwright E2E tests in a debug mode (with Chromium browser and dev tools open).
- `npm run switch-to:php7.4` and `npm run switch-to:php8.2` will switch you to either PHP 7.4 or PHP 8.2
- `npm run document:connectors` generates [connectors.md](connectors.md). This runs via your local php.
- `npm run large-records-generate` inserts ~1.6M rows to `wp_stream` and ~8.4M rows to `wp_streammeta` for testing
- `npm run large-records-remove` removes the test data only
- `npm run large-records-show` shows how much test data is in the tables, this does not include non-test entries

By default, tests have `WP_DEBUG` as false. You can override this if necessary by setting `WP_STREAM_TEST_DEBUG` to "yes".

### Docker issues

If you are having issues with incorrect versions of Xdebug or other Docker issues, first try rebuilding with no cache and up to date images using the command `docker compose build --no-cache --pull`. Then run `npm run start` as normal.

## Issues Tracker

Support issues or usage questions should be posted on the [Plugin Support Forum](https://wordpress.org/support/plugin/stream).

The [issue tracker on GitHub](https://github.com/xwp/stream/issues) is the preferred channel for [bug reports](#bugs), [features requests](#features) and [submitting pull requests](#pull-requests).


<a name="bugs"></a>

## Reporting Bugs

A bug is a _demonstrable problem_ that is caused by the code in the repository. Good bug reports with complete error messages, environment details and screenshots are extremely helpful &mdash; thank you!

Guidelines for bug reports:

1. **Check if the bug has already been fixed** &mdash; Someone may already be on top of it, so try to reproduce it using the latest from the `master` branch.

2. **Use the [GitHub issue search](https://github.com/xwp/stream/search?type=Issues)** &mdash; Someone might already know about it, so please check if the issue has already been reported.

3. **Isolate the problem** &mdash; The better you can determine exactly what behavior(s) cause the issue, the faster and more effectively it can be resolved. “I’m getting an error message.” is not a good bug report. A good bug report shouldn't leave others needing to contact you for more information.

Please try to be as detailed as possible in your report. What is your environment? What steps will reproduce the issue? What browser(s) experience the problem? What outcome did you expect, and how did it differ from what you actually saw? All these details will help people to fix any potential bugs.

Example:

> Short and descriptive example bug report title
>
> A summary of the issue and the environment/browser in which it occurs. If
> suitable, include the steps required to reproduce the bug.
>
> 1. This is the first step
> 2. This is the second step
> 3. Further steps, etc.
>
> Any other information you want to share that is relevant to the issue being reported. This might include the lines of code that you have identified as causing the bug, and potential solutions (and your opinions on their merits).

**Note:** In an effort to keep open issues to a manageable number, we will close any issues that do not provide enough information for us to be able to work on a solution. You will be encouraged to provide the necessary details, after which we will reopen the issue.


<a name="features"></a>

## Feature Requests

Feature requests are very welcome! But take a moment to find out whether your idea fits with the scope and aims of the project. It's up to *you* to make a strong case to convince the project's developers of the merits of this feature. Please provide as much detail and context as possible.

Building something great means choosing features carefully especially because it is much, much easier to add features than it is to take them away. Additions to Stream will be evaluated on a combination of scope (how well it fits into the project), maintenance burden and general usefulness to users.


<a name="pull-requests"></a>

## Pull Requests

Good pull requests &mdash; patches, improvements, new features &mdash; are a fantastic help.
They should remain focused in scope and avoid containing unrelated commits.

**Please ask first** before embarking on any significant pull request (e.g. implementing features, refactoring code), otherwise you risk spending a lot of time working on something that the project's developers might not want to merge into the project. You can solicit feedback and opinions in an open enhancement issue, or [create a new one](https://github.com/xwp/stream/issues/new).

Please use the [git flow for pull requests](#git-flow) and follow [WordPress Coding Standards](https://make.wordpress.org/core/handbook/coding-standards/) before submitting your work.


<a name="git-flow"></a>

### Git Flow for Pull Requests

1. [Fork](https://help.github.com/fork-a-repo/) the project, clone your fork, and configure the remotes:

   ```bash
   # Clone your fork of the repo into the current directory
   git clone git@github.com:<YOUR_USERNAME>/stream.git
   # Navigate to the newly cloned directory
   cd stream
   # Assign the original repo to a remote called "upstream"
   git remote add upstream https://github.com/xwp/stream
   ```

2. If you cloned a while ago, get the latest changes from upstream:

   ```bash
   git checkout master
   git pull upstream master
   ```

3. Create a new topic branch (off the `master` branch) to contain your feature, change, or fix:

   ```bash
   git checkout -b <topic-branch-name>
   ```

4. Commit your changes in logical chunks. Please adhere to these [git commit message guidelines](https://tbaggery.com/2008/04/19/a-note-about-git-commit-messages.html). Use Git's [interactive rebase](https://help.github.com/articles/interactive-rebase) feature to tidy up your commits before making them public.

5. Locally merge (or rebase) the upstream development branch into your topic branch:

   ```bash
   git pull [--rebase] upstream master
   ```

6. Push your topic branch up to your fork:

   ```bash
   git push origin <topic-branch-name>
   ```

7. [Open a Pull Request](https://help.github.com/articles/using-pull-requests/) (with a clear title and description) to the `develop` branch.

**IMPORTANT**: By submitting a patch, you agree to allow the project owner to license your work under the [GPL v2 license](https://www.gnu.org/licenses/gpl-2.0.html).

## Release Cycle

The plugin versioning follows [semantic versioning](https://semver.org).

### Pre-release

Features, bug fixes, and other changes are assigned to a milestone. Once all issues in a milestone are closed:

1. **Create Release Branch:**
   - Branch off from `develop`.
   - Name it `release/vX.Y.Z`, where `X.Y.Z` is the version number.

2. **Update Metadata:**
   - Update the plugin version, changelog and other relevant information.

3. **Create Pre-release in GitHub:**
   - Name the release like `X.Y.Z-rc.N`, e.g. `4.0.1-rc.1`.
   - The tag name should be prefixed with `v`, e.g. `v4.0.1-rc.1`.

4. **Review and Test:**
   - Publishing a pre-release will trigger a GitHub action.
   - A dry-run of WP.org deployment will occur (no files are committed).
      - Review the SVN changes log in the action output.
   - A ZIP archive with the plugin is created and uploaded as a release asset.
      - Use that ZIP file for final testing.

5. **Fix Issues:**
   - If any issues are found, fix them in the release branch.
   - Repeat the process from step 3.

### Release

Once ready, follow these steps:

1. **Create Release in GitHub:**
   - Name the release like `X.Y.Z`, e.g. `4.0.1`.
   - The tag name should be prefixed with `v`, e.g. `v4.0.1`.

2. **Confirm Deployment:**
   - The GitHub action deploys the plugin to WP.org.
      - Confirm the changes have been deployed to SVN in the [plugin trac](https://plugins.trac.wordpress.org/browser/stream/).
   - A ZIP archive is created and uploaded to GitHub release assets.

3. **Merge Branches:**
   - Merge the release branch into `master`.
   - Merge `master` into `develop`.

By following this process, you ensure a smooth and consistent release cycle.
