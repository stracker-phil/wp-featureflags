# ADR-005: WP Admin Bar as UI Surface

**Status:** Accepted

## Context

Feature flags need to be toggled quickly during development without navigating to a settings page. The WP admin bar is always visible on both front-end and admin pages.

## Decision

Both Feature Flags and Feature Actions render as dropdown menus in the WordPress admin bar (priorities 100 and 101). Only users with `manage_options` capability see the menus. Inline CSS styles the items; inline JS handles click interactions via AJAX.

## Consequences

- Flags are always one click away — no page navigation needed.
- Limited screen real estate; works well for dozens of items but not hundreds.
- Inline CSS/JS avoids asset registration but cannot be cached separately.
