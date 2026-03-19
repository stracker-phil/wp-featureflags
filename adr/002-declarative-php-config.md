# ADR-002: Declarative PHP Array Config

**Status:** Accepted

## Context

Flags and actions need to be easy to add, remove, and review. A database-backed UI would be overkill for a developer tool, and YAML/JSON would lose the ability to use PHP callables (e.g., `getenv()` checks for defaults).

## Decision

Configuration lives in plain PHP files (`flags.php`, `actions.php`) that return associative arrays. Each entry is a self-contained definition with a label and either a WP filter name (flags) or a list of change operations (actions). Group headings are label-only entries.

## Consequences

- Config is version-controlled and diffable.
- PHP callables can be used for dynamic defaults (e.g., env-var checks).
- No admin UI for editing config — changes require file edits and redeployment.
