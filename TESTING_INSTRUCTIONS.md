# Testing Instructions — Issue #1842

## What this fix does

`Ultimate Member` was unconditionally enqueuing its global admin script
(`um_admin_global` JS + CSS) and the localized `um-admin-nonce` on every
single wp-admin page. That script is only needed for the UM admin notice
dismiss handler and the UM upgrade page. Enqueuing it on unrelated admin
pages (Elementor editor, Real Media Library, ACF, etc.) interfered with
the `wp-auth-check` / heartbeat load order in WP 6.9, which broke the
nonce-refresh path and caused the site-wide
`rest_cookie_invalid_nonce` errors reported in issue #1842.

This patch scopes `um_admin_global` enqueue to UM-owned screens, with a
filter `um_enqueue_global_admin_scripts` so third-party code can opt back
in if it renders UM notices on other screens.

## Requirements

- WordPress 6.2+ (tested target: 6.9)
- PHP 7.0+
- Ultimate Member 2.12.1 (the fix is backported to the next release)

## Install

1. Backup your current `wp-content/plugins/ultimate-member/` directory.
2. Extract `ultimatemember-fix-1842.zip` over the existing install:
   ```bash
   unzip -o ultimatemember-fix-1842.zip -d wp-content/plugins/
   ```
3. Activate Ultimate Member (or keep it active, the change is hot-applied).
4. Clear any server-side + LiteSpeed cache and any browser cache/cookies.

## Test steps (the user's reproduction)

Repro from issue #1842:
- WordPress 6.9.x
- Ultimate Member (latest)
- Elementor Pro, ACF Pro, Real Media Library Pro, LiteSpeed Cache, SearchWP,
  Fluent plugins, Code Snippets, Frontend Admin

1. Log in as an admin in a fresh Chrome window.
2. Open DevTools → Console and Network tabs.
3. **Before fix:** console shows
   `Uncaught TypeError: Cannot read properties of undefined (reading 'hasClass') in wp-auth-check.min.js via heartbeat.min.js`. Network tab shows REST requests to `/wp-json/...` and `admin-ajax.php?action=heartbeat` returning `rest_cookie_invalid_nonce`.
4. **After fix:**
   - No `wp-auth-check.min.js` TypeError in the console.
   - REST calls return 200.
   - Heartbeat ticks succeed (Network → `admin-ajax.php?action=heartbeat` returns `wp-auth-check: true`).
   - "Your session has expired" modal does not appear.
5. Open Elementor editor → open a page → save → close. Repeat several times.
6. Open Real Media Library → perform operations.
7. Open ACF field groups → save.
8. Idle for 5 minutes with DevTools recording → confirm heartbeats keep succeeding.

## Regression checks (UM admin still works)

1. Go to `wp-admin → Users → All Users` (UM screen, `is_own_screen()` = true).
   - Confirm a UM admin notice (if any are queued) shows a dismiss "X" that works.
2. Go to `wp-admin → Users → Profile` → confirm UM profile fields render.
3. Go to `wp-admin → Ultimate Member → Settings` → confirm admin notices dismiss.
4. Go to `wp-admin → Forms` (UM CPT list) → confirm the page works.
5. Edit a UM Form → confirm the builder loads.
6. WP admin dashboard (`index.php`) — if a UM notice happens to render
   there (e.g. the "create core pages" notice), the dismiss button will be
   dead. This is an edge case documented in the PR; if you need it to work,
   set:
   ```php
   add_filter( 'um_enqueue_global_admin_scripts', '__return_true' );
   ```

## Filter reference (for extension authors)

```php
/**
 * Force the UM global admin script to load on all admin pages
 * (use only if you render UM admin notices on non-UM screens).
 */
add_filter( 'um_enqueue_global_admin_scripts', '__return_true' );

/**
 * Per-screen opt-in: load only on a specific wp-admin screen hook.
 */
add_filter( 'um_enqueue_global_admin_scripts', function( $enqueue, $hook ) {
    return 'plugins.php' === $hook ? true : $enqueue;
}, 10, 2 );
```

## Reporting

If the fix does not resolve the issue on your install, capture:
- Browser console log (filter to errors and warnings).
- Network log for `admin-ajax.php?action=heartbeat` and one
  `rest_cookie_invalid_nonce` request.
- `wp-content/debug.log` if `WP_DEBUG_LOG` is on.
- Output of `git -C wp-content/plugins/ultimatemember log -1 -- includes/admin/class-enqueue.php`.

## Files changed

- `includes/admin/class-enqueue.php` — only file modified.
  - `admin_enqueue_scripts()` now gates `load_global_scripts()` behind
    `is_own_screen()` and the new `um_enqueue_global_admin_scripts` filter.
  - `load_global_scripts()` PHPDoc updated to note the caller-side gate.
