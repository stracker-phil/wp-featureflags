---
name: session-end
description: Review session work and update project docs. Use at the end of a coding session, when wrapping up, or when the user says "session end", "wrap up", "end of session", or "update docs".
disable-model-invocation: true
---

# Session End

Review everything done and discussed in this session and bring project documentation up to date.

Execute all steps below before finishing.

## Step 1: Identify changes

Review all files created, modified, or deleted during this session. Build a list of what changed and why.

## Step 2: Check ADRs

Compare the session's changes against existing ADR files in `adr/`.

- If a change introduces a **new** architectural decision (new tool, new pattern, new convention, structural change), create a new ADR file following the existing naming and format.
- If a change **modifies or reverses** an existing decision, update the relevant ADR: set its status to `Superseded` or `Amended` and explain what changed.
- If no architectural decisions were made or changed, skip this step — do not create ADRs for routine code changes.

## Step 3: Update CLAUDE.md

Re-read the current `CLAUDE.md` and compare it against the project's actual state.

- Update commands if build/test/lint scripts changed.
- Update project structure if files or directories were added or reorganized.
- Update key patterns if new conventions or architectural approaches were introduced.
- Remove anything that's no longer accurate.

If nothing in CLAUDE.md needs changing, say so explicitly and move on.

## Step 4: Summary

Provide a brief summary of documentation changes made (or confirm none were needed). Format:

```
### Docs updated
- [what changed and why]

### No changes needed
- [area]: [why it's still accurate]
```
