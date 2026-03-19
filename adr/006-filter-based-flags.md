# ADR-006: Filter-Based Flag Mechanism

**Status:** Accepted

## Context

The plugin needs to override feature flags in other plugins without modifying their code. WordPress filters are the standard extension point for this.

## Decision

Each flag maps to a WP filter name. On `plugins_loaded`, the plugin adds a filter callback at priority 99999 that returns `true` or `false` based on the stored state. The "default" state adds no filter, letting the target plugin's own logic run. Flag states are persisted in a single `wp_feature_flags` option as a key-value array.

## Consequences

- Works with any plugin that uses `apply_filters` for feature gating.
- Priority 99999 ensures this plugin's override wins over most other filters.
- The `plugins_loaded` hook timing means flags are active before most plugin initialization.
