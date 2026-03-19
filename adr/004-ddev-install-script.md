# ADR-004: DDEV Deployment via Install Script

**Status:** Accepted

## Context

The plugin must be installed into a DDEV-based WordPress project's `wp-content/plugins/` directory. Symlinks can cause issues with DDEV's Docker volume mounts, and Composer is not used for this utility.

## Decision

A bash `install.sh` script copies plugin files into the target project at `.ddev/wordpress/wp-content/plugins/wp-featureflags/`. Core files (`plugin.php`, config files) are always overwritten. Local override files are only copied if they don't already exist, preserving personal customizations.

## Consequences

- Installation is a single command: `bash install.sh <project-path>`.
- Must re-run install after changing `plugin.php`, `flags.php`, or `actions.php`.
- Local files survive reinstallation.
