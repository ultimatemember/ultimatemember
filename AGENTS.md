# Ultimate Member Agent Instructions

These rules apply to this plugin. Use its Git repository root (the directory containing `ultimate-member.php`) as the working directory. Check for additional instructions in the directories you change.

## Compatibility

- The runtime minimums are PHP 7.0 and WordPress 6.2. Use the headers in `ultimate-member.php` and `readme.txt` as the source of truth. Changing minimum requirements requires an explicit request.
- Keep distributed PHP compatible with the minimum and modern PHP versions. Avoid syntax introduced after PHP 7.0, such as arrow functions, typed properties, and `??=`. Runtime guards can protect unavailable APIs but cannot protect unsupported syntax. Avoid dynamic properties and passing `null` to non-nullable built-in parameters. Provide a working fallback for WordPress APIs introduced after the minimum version.
- Development tool requirements are separate from plugin runtime requirements. Follow `phpcs.xml` (WordPress-Extra and PHPCompatibilityWP), `.editorconfig`, and nearby code. For a justified PHPCS suppression, name the specific sniff and explain the false positive.

## Plugin conventions

- Reuse the existing classes and loading conventions in `includes/`, template overrides in `templates/`, and `um_` / `UM_` naming conventions. Keep `templates/` focused on rendering and escaping. Put request handling and business logic in `includes/`. Document new public APIs and hooks using the existing PHPDoc and `@since` conventions.
- Preserve public signatures, hook names and arguments, option and meta keys, REST/AJAX contracts, and template overrides. Intentional contract changes require a compatibility strategy.
- Translate new and changed user-facing strings using the `ultimate-member` text domain and existing PHP and JavaScript conventions. Use placeholders for dynamic content and translator comments where the meaning is ambiguous. Preserve keyboard navigation, accessible labels, and error states when changing UI.
- Use WordPress enqueue APIs and existing dependencies. Consider pagination, caching, and multisite where relevant.
- Edit sources for generated assets. Block sources live in `blocks-src/` and build into `includes/blocks/`. Keep dependency directories and bundled third-party libraries in `includes/lib/` out of routine edits. Preserve unrelated changes, versions, lockfiles, and validation settings.

## Security and data handling

- Validate input shape, types, allowed values, and object existence. Apply `wp_unslash()` to slashed request values, sanitize according to the field, and escape at output for the rendering context. Use explicit allowlists for writable fields, especially user metadata, roles, and capabilities.
- Validate ID shape and range before normalization. `absint()` can turn a negative value into a positive ID. Check that the target exists and is accessible before acting on it.
- Check capability and object-level access for the current action and target independently of nonce verification. Verify nonces where CSRF protection is needed. For AJAX actions that require a nonce, use `check_ajax_referer()` with the expected action and request field. Define REST `permission_callback` explicitly.
- Prefer WordPress APIs for queries, metadata, uploads, and redirects. For direct SQL, use `$wpdb->prepare()` for values and allowlist dynamic query choices. The `%i` identifier placeholder is available since WordPress 6.2. Check `$wpdb->has_cap( 'identifier_placeholders' )` when relying on it with alternate database implementations.
- Handle empty results and `WP_Error`. Keep credentials and personal data out of logs and error messages.

## Verification

- For changes to PHP, blocks, or plugin behavior, use `verify-um-change` or follow [the shared verification workflow](docs/agent-workflows/verify-um-change.md) directly.
- For documentation-only changes, review the content, links, and diff. Report the checks you ran and any relevant checks you could not run.
