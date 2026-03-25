# ADR-008: GitHub Actions Release Workflow

**Status:** Accepted

## Context

The plugin is distributed by copying PHP files into a WordPress project. Releases need to bundle the relevant PHP files into a zip for easy download, and should be versioned using semantic version tags.

## Decision

A GitHub Actions workflow (`.github/workflows/release.yml`) triggers on pushes of tags matching `v[0-9]+.[0-9]+.[0-9]+`. It zips the four distributable PHP files (`plugin.php`, `actions.php`, `flags.php`, `snippets.local.php`) into `wp-featureflags.zip` and publishes a GitHub release with auto-generated notes.

Cutting a release is a single command:
```sh
git tag v1.0.0 && git push origin v1.0.0
```

## Consequences

- Releases are versioned and traceable via git tags.
- The zip contains only the distributable PHP files — not local overrides (`.local.php` stubs are included as starting-point templates).
- No build step is required; the workflow zips source files directly.
