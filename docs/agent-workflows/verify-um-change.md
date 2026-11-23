# Verify an Ultimate Member change

Run checks from the plugin Git root in the current shell. Use relative paths and quote paths containing spaces. Use the project's PHP, Composer, and npm tools. When PHP is outside PATH, use the local executable configured for the project. The PHPCS command below invokes the Composer proxy through PHP and works with PowerShell and macOS shells.

## PHP changes

- Review the diff, including newly added files, for scope, unintended output, and secrets.
- Run `php -l "path/to/file.php"` for each changed or added PHP file that still exists.
- Run `php vendor/bin/phpcs --standard=phpcs.xml --parallel=1 "path/to/file.php"` on the affected PHP files. Pass multiple paths to PHPCS when appropriate. Use local dependencies installed from the plugin's Composer lockfile. The full CI run uses PHP 8.4 and scans the plugin root with `phpcs.xml`.
- Check PHP and WordPress API compatibility against the runtime minimums in the plugin headers. The current `phpcs.xml` sets `testVersion="5.6-"` even though the plugin requires PHP 7.0. Keep the ruleset as configured and report the mismatch if it affects the results. A lint pass on a modern PHP binary checks that binary's syntax only.

## Behavior changes

- Run existing regression checks that cover the changed path. The standalone checks include `php tests/php/download-routing-regression.php` for download routing and `php tests/php/user-capability-mapping-regression.php` for user capability mapping. Inspect the relevant test before generalizing its coverage.
- For a behavior fix, add a focused regression test where the existing test setup supports it. Otherwise, exercise the affected scenario on a local WordPress site. For profile and permission changes, cover the applicable owner, other user, administrator, and logged-out cases, including denied access and invalid input.
- When the environments are available, exercise compatibility-sensitive changes on the minimum supported versions and on a modern supported setup. Record the runtime versions used and distinguish static checks from WordPress runtime checks.

## Blocks and other assets

- For changes under `blocks-src/`, run `npm run build:all-blocks` and review the resulting changes under `includes/blocks/`.
- For other asset changes, select the relevant task from `gulpfile.js` or `package.json`. The `build:docs` task uses `rm -rf`, and the `.sh` test helpers require a POSIX-compatible shell. On Windows, use the relevant POSIX task in a compatible environment only when it applies.

Summarize what passed, what failed, and which applicable checks were skipped because a tool or test environment was unavailable.
