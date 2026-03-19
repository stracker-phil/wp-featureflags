# ADR-007: AJAX Toggle with Nonce Verification

**Status:** Accepted

## Context

Toggling flags and running actions modify WordPress state. These operations must be protected against CSRF and unauthorized access.

## Decision

All state-changing operations go through `wp_ajax_*` hooks. Each request requires a valid nonce and `manage_options` capability. Nonces are embedded in the inline JS at render time. Responses use `wp_send_json_success` / `wp_send_json_error`.

## Consequences

- Standard WordPress security pattern — no custom auth layer needed.
- Nonces expire, so very long-lived pages may need a refresh before toggling.
- Only admin users can toggle; no granular per-flag permissions (not needed for a dev tool).
