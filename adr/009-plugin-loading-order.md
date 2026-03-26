# ADR-009: Plugin Loading Order Guarantee

**Status:** Accepted

## Context

Feature flags are evaluated during `plugins_loaded`. WordPress loads plugins in the order they appear in the `active_plugins` option (or `active_sitewide_plugins` on multisite). If this plugin is activated *after* a target plugin, its `plugins_loaded` callback fires later, meaning flags aren't in place when the target plugin initializes.

Additionally, plugins that hook into `plugins_loaded` with a negative priority could initialize before the default priority 10 callback.

## Decision

Two mechanisms ensure this plugin's flags are active before any other plugin reads them:

1. **Activation hook** — On activation, the plugin moves itself to the first position in the active-plugins array. This handles both single-site (`active_plugins` option) and multisite network-wide activation (`active_sitewide_plugins` site option). MU plugins don't need this because WordPress always loads them before regular plugins.

2. **Early `plugins_loaded` priority** — The bootstrap callback runs at priority `-100` instead of the default `10`, so even if another plugin registers a `plugins_loaded` handler with a low priority, this plugin's initialization (and flag filter registration) runs first.

## Consequences

- Users must deactivate and reactivate the plugin if it was already active before this change, to trigger the reordering.
- The `-100` priority is an arbitrary low value; a plugin using a lower priority would still run first, but this covers all realistic cases.
- Network-activated plugins already load before per-site plugins, so the activation hook only needs to ensure first position within the same group.
