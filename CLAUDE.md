# WP Feature-Flag Manager

WordPress admin-bar plugin for toggling feature flags and running one-click database actions during development. Works with any plugin that uses WP filters.

## Quick Start

```sh
# Install into a DDEV WordPress project
bash install.sh ~/Coding/my-wp-project
```

No build step, no Composer, no dependencies. The install script copies files into `.ddev/wordpress/wp-content/plugins/wp-featureflags/`.

## Project Structure

```
plugin.php                      # All plugin code — single file, namespace WpFeatureFlags
flags.php                       # Shared feature flag definitions (PHP array)
actions.php                     # Shared action definitions (PHP array)
*.local.php                     # Personal overrides — git-ignored, not overwritten on install
snippets.local.php              # Local arbitrary PHP loaded at runtime
install.sh                      # Deploys plugin to a target DDEV project
.github/workflows/release.yml  # Creates a GitHub release zip on semver tag push
adr/                            # Architecture Decision Records
```

## Configuration

- **Add a flag:** Add an entry to `flags.php` with `label`, `filter`, and optional `default`/`display` keys.
- **Add an action:** Add an entry to `actions.php` with `label` and a `changes` array of operations (`set_option`, `set_option_key`, `delete_option`, `do_action`).
- **Group headings:** A plain string entry or an entry with only a `label` key renders as a non-clickable separator. A dash-only string (e.g. `'-'` or `'---'`) renders as an `<hr>` divider line.
- **Local overrides:** `*.local.php` files are loaded instead of the base file (first-file-wins via ConfigLoader). The shipped stubs `require` the base and `array_merge` on top, but a dev can return any array — merge, replace, or selectively `unset()` entries. See [ADR-003](adr/003-local-override-pattern.md).

## Architecture

- Single-file plugin with inline CSS/JS — no asset pipeline (see [ADR-001](adr/001-single-file-plugin.md))
- Config is declarative PHP arrays supporting callables for defaults (see [ADR-002](adr/002-declarative-php-config.md))
- Local override files (`*.local.php`) for per-developer customization (see [ADR-003](adr/003-local-override-pattern.md))
- Flags work by injecting WP filters at priority 99999 on `plugins_loaded` (see [ADR-006](adr/006-filter-based-flags.md))
- State stored in `wp_feature_flags` WP option; toggled via AJAX with nonce auth (see [ADR-007](adr/007-ajax-nonce-security.md))
- Admin bar menus visible only to users with `manage_options` capability

## Conventions

- PHP namespace: `WpFeatureFlags`
- Config keys use slash-separated IDs: `myplugin/feature_name`
- Filters target the WP filter used by the relevant plugin (e.g. `woocommerce.feature-flags.<plugin>.<flag>`)
- No tests or linter — this is a lightweight dev utility
- Releases are cut by pushing a semver tag (`git tag v1.0.0 && git push origin v1.0.0`); GitHub Actions builds the zip (see [ADR-008](adr/008-github-release-workflow.md))
