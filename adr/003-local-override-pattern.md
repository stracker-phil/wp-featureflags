# ADR-003: Local Override Files (*.local.php)

**Status:** Accepted

## Context

Different developers may need different flags or actions for their local environment, but the shared defaults should stay in version control.

## Decision

Each config file has a `.local.php` variant (`flags.local.php`, `actions.local.php`). ConfigLoader takes a priority-ordered list of filenames and returns the first one it finds — it does **not** merge files itself. The merge/replace logic lives entirely inside the local file, giving it full control over the returned array.

The shipped stubs `require` the base file and `array_merge` on top, but a developer can:
- **Extend:** keep the stub as-is and add entries to `$local_flags` (default)
- **Replace:** return a plain array, ignoring the base file entirely
- **Selectively remove:** `require` the base, `unset()` entries, then return

`snippets.local.php` is separate — it is `require_once`'d directly from the plugin bootstrap, not loaded through ConfigLoader. It has no base file; it exists purely for arbitrary local PHP code.

Local files are git-ignored. `install.sh` copies stub templates only if the local file doesn't already exist, preserving personal customizations across reinstalls.

## Consequences

- Personal customization without polluting the shared config.
- The local file owns the return value — maximum flexibility with minimal framework.
- New developers get working stubs automatically via `install.sh`.
- Removal of base entries requires explicit `unset()` — not obvious from the stub alone.
