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
*.sample.php                    # Templates copied to config dir on first load
changelog.txt                   # Version history with categorised entries (New/Improve/Fix/Change/Chore)
install.sh                      # Deploys plugin to a target DDEV project
.github/workflows/release.yml  # Creates a GitHub release zip on semver tag push
adr/                            # Architecture Decision Records
```

Local config files live **outside** the plugin directory in `wp-content/<plugin-folder>/` (derived from the plugin directory name) so they persist across plugin updates. On first load, `*.sample.php` templates are copied there as `*.php`:

```
wp-content/
  wp-featureflags/              # Persistent config dir — survives plugin updates
    flags.php                   # Local flag overrides (from flags.sample.php)
    actions.php                 # Local action overrides (from actions.sample.php)
    links.php                   # Local quick-link definitions (from links.sample.php)
    snippets.php                # Local PHP snippets (from snippets.sample.php)
  plugins/
    wp-featureflags/            # Plugin dir — replaced on update
      plugin.php
      flags.php                 # Base flag definitions
      actions.php               # Base action definitions
      *.sample.php              # Templates for the config dir
```

## Configuration

- **Add a flag:** Add an entry to `flags.php` with `label`, `filter`, and optional `default`/`display` keys.
- **Add an action:** Add an entry to `actions.php` with `label` and a `changes` array of operations (`set_option`, `set_option_key`, `delete_option`, `do_action`).
- **Add a quick link:** Add an entry to `links.php`. Supports shorthand `['Label', '/url']`, named `['label' => 'Label', 'href' => '/url']`, groups `'Group' => [...]`, dividers `'---'`, and plain string headings. The menu only appears when the array is non-empty.
- **Group headings:** A plain string entry or an entry with only a `label` key renders as a non-clickable separator. A dash-only string (e.g. `'-'` or `'---'`) renders as an `<hr>` divider line.
- **Local overrides:** Edit config files in `wp-content/wp-featureflags/`. For `flags.php` and `actions.php`, the config dir is checked first; if a file isn't found there, the base file from the plugin dir is used. The shipped samples `require` the base via `WP_FEATUREFLAGS_DIR` and `array_merge` on top, but a dev can return any array — merge, replace, or selectively `unset()` entries. `links.php` and `snippets.php` are user-only (no base file in the plugin dir). See [ADR-003](adr/003-local-override-pattern.md).

## Architecture

- Single-file plugin with inline CSS/JS — no asset pipeline (see [ADR-001](adr/001-single-file-plugin.md))
- Config is declarative PHP arrays supporting callables for defaults (see [ADR-002](adr/002-declarative-php-config.md))
- Local config in `wp-content/wp-featureflags/` for per-developer customization that survives plugin updates (see [ADR-003](adr/003-local-override-pattern.md))
- Flags work by injecting WP filters at priority 99999 on `plugins_loaded` (see [ADR-006](adr/006-filter-based-flags.md))
- Plugin forces itself to load first: activation hook reorders `active_plugins` / `active_sitewide_plugins`, bootstrap runs at `plugins_loaded` priority -100 (see [ADR-009](adr/009-plugin-loading-order.md))
- State stored in `wp_feature_flags` WP option; toggled via AJAX with nonce auth (see [ADR-007](adr/007-ajax-nonce-security.md))
- Admin bar menus visible only to users with `manage_options` capability

## Conventions

- PHP namespace: `WpFeatureFlags`
- Config keys use slash-separated IDs: `myplugin/feature_name`
- Filters target the WP filter used by the relevant plugin (e.g. `woocommerce.feature-flags.<plugin>.<flag>`)
- No tests or linter — this is a lightweight dev utility
- Releases are cut by pushing a semver tag (`git tag v1.0.0 && git push origin v1.0.0`); GitHub Actions builds the zip (see [ADR-008](adr/008-github-release-workflow.md))
