# ADR-003: External Config Directory

**Status:** Accepted (revised)

## Context

Different developers may need different flags or actions for their local environment, but the shared defaults should stay in version control. WordPress plugin updates replace the entire plugin directory, so config files stored inside it would be lost.

## Decision

Local config files are stored in `wp-content/<plugin-folder>/`, a persistent directory outside the plugin folder (derived from the plugin directory name via `basename(__DIR__)`). This directory survives plugin updates whether installed via zip upload, WP-CLI, or `install.sh`.

The plugin ships `*.sample.php` templates alongside its base config. On first load, each sample is copied to the config dir as `*.php` (e.g. `flags.sample.php` → `wp-content/wp-featureflags/flags.php`). Existing files are never overwritten.

ConfigLoader takes a **filename** and an ordered list of **search paths**. It checks the filename in each path in order and returns the first hit. The default search order is:

1. `WP_CONTENT_DIR/<plugin-folder>/` (external config dir)
2. Plugin directory (where base files like `flags.php` live)

Both directories use the **same filenames** (`flags.php`, `actions.php`, `snippets.php`) — the search path priority alone determines which file wins.

Two PHP constants are defined for use in config files:
- `WP_FEATUREFLAGS_DIR` — the plugin directory (for referencing base config files)
- `WP_FEATUREFLAGS_CONFIG_DIR` — the external config directory

The shipped samples `require` the base file via `WP_FEATUREFLAGS_DIR` and `array_merge` on top, but a developer can:
- **Extend:** keep the sample as-is and add entries (default)
- **Replace:** return a plain array, ignoring the base file entirely
- **Selectively remove:** `require` the base, `unset()` entries, then return

`snippets.php` is separate — it is `require_once`'d directly from the plugin bootstrap (config dir only). It has no base file; it exists purely for arbitrary local PHP code.

`install.sh` copies sample templates to the external config dir only if the target file doesn't already exist, preserving personal customizations across reinstalls.

## Consequences

- Local config persists across plugin updates (zip install, WP-CLI, or manual).
- Personal customization without polluting the shared config.
- The config file owns the return value — maximum flexibility with minimal framework.
- New installs get working defaults automatically (bootstrapped on first load, or via `install.sh`).
- Sharing local config between devs = copy the `wp-content/wp-featureflags/` folder.
- Only three filenames matter everywhere: `flags.php`, `actions.php`, `snippets.php`.
