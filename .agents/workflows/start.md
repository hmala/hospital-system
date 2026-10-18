---
name: start
description: Start or resume hospital system session. Reads current project state from hospital-memory.md and executes the task.
---

# Workflow: Start / Resume Hospital System Session

When this command is invoked:
1. Read `hospital-memory.md` to get the `Current Project State` (Completed, In Progress, Next Up).
2. Check `.agents/rules/hospital-core.md` for the domain architecture blueprint.
3. If the user provided a task after `/start [task]`:
   - Proceed immediately to work on that task following the coding standards.
4. If no task was provided:
   - Provide a concise summary of the latest completed phase and ask the user what to work on next.
