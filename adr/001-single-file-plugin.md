# ADR-001: Single-File Plugin Architecture

**Status:** Accepted

## Context

The plugin is a small development utility — not a product with many modules. Introducing an autoloader, directory structure, or Composer would add overhead with no benefit.

## Decision

All PHP classes (ConfigLoader, AdminBarMenu, FeatureFlags, FeatureActions) live in a single `plugin.php` file under the `WpFeatureFlags` namespace. Inline `<style>` and `<script>` blocks are rendered directly from PHP methods — no separate asset files or build pipeline.

## Consequences

- Very fast to deploy: copy one file and it works.
- Easy to read top-to-bottom; no jumping between files.
- If the plugin grows significantly, this file will become unwieldy — but that growth is unlikely given its purpose.
