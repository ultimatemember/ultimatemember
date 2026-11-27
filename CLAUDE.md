# Ultimate Member – notes for Claude and contributors

WordPress plugin (`ultimate-member.php`, PHP ≥ 7.0, WP ≥ 6.2). Main code lives in `includes/`;
the `UM()` singleton (`includes/class-init.php`) lazily creates services such as `UM()->roles()`,
`UM()->user()`, `UM()->options()`, `UM()->common()->users()`.

## Tests

Integration tests run PHPUnit 9.6 with the official WordPress test suite (`WP_UnitTestCase`) against a
real WordPress + MySQL in Docker (`wp-env`). Each test runs in a DB transaction that is rolled back.

Requirements: Docker running, Node ≥ 22.13, `npm install` done once (provides `wp-env`).

```sh
npm run test:php:setup                      # once: start the tests environment, install PHPUnit in it
npm run test:php                            # run all tests; exit code ≠ 0 on failure
npm run test:php -- --filter UserRolesTest  # a single class / test
npm run test:php -- --group security        # a group
npm run test:env:stop                       # stop containers (test:env:destroy removes them)
```

- The tests environment is configured in `.wp-env.tests.json` (port 8889) and is separate from any dev
  site. Running PHPUnit wipes and reinstalls its database; do not point it at data you care about.
- Local overrides (PHP version, ports): `.wp-env.tests.override.json` (git-ignored).
- PHPUnit + Yoast Polyfills are installed **inside the container** from `tests/phpunit/composer.json`, so
  they always match the container PHP. Do not add them to the root `composer.json`.
- `includes/lib/` (Action Scheduler etc.) comes from the root `composer install --no-dev`; it must exist.
- CI: `.github/workflows/phpunit.yml` runs the same commands on PHP 7.4 + WP 6.2 and PHP 8.4 + latest WP.

### Layout

- `tests/phpunit/bootstrap.php` – loads WP test suite, loads the plugin, runs UM setup (default settings
  and role meta) once per run.
- `tests/phpunit/includes/class-um-unittestcase.php` – base class `UM_UnitTestCase`: `create_um_role()`,
  `update_role_meta()`, `create_user_with_roles()`, `act_as()`, `reload_roles()`, `create_core_page()`,
  `capture_redirect()` (returns the URL of a `wp_safe_redirect()` + `exit` without exiting),
  `create_um_form()` (Login/Register/Profile form from the default core form fields; bootstrap doesn't
  install the core forms), `submit_um_form()` (POST through `UM()->form()->form_init()` with the
  `-{form_id}` field suffix), `call_ajax()` (runs an AJAX handler, returns the decoded JSON or -1).
  `set_up()` re-reads UM settings (so `UM()->options()->update()` doesn't leak into the next test) and
  clears `$_GET`/`$_POST`/`$_REQUEST`/`$_COOKIE`, `$_um_profile_id` and UM form state (errors, `post_form`,
  preview flags).
- `tests/phpunit/tests/<area>/<Name>Test.php` – tests; class names `UM_<Area>_<Name>Test`.

### Rules

- New logic or a bug fix comes with a test in the same change. A bug fix starts with a failing test.
- Extend `UM_UnitTestCase`, not `WP_UnitTestCase`. Use factories and the helpers; no hard-coded IDs.
- Combinations (roles × settings × expected result) go into a `@dataProvider` with named cases.
- Use `set_up()` / `tear_down()` (snake_case, from the polyfills), not `setUp()`.
- Tag security-relevant tests with `@group security`.
- A confirmed bug that is not fixed in the same change gets a failing test tagged `@group known-bug`
  (excluded by default in `phpunit.xml.dist`). The fix removes the tag.
- Keep the suite fast (well under 2 minutes): no external HTTP, no `sleep()`.
- Code must stay PHP 7.0 compatible (`phpcs.xml` has PHPCompatibilityWP); tests need PHP ≥ 7.4.

## Coding standards

`vendor/bin/phpcs --standard=phpcs.xml <files>` (WordPress Coding Standards).
