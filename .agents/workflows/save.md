---
name: save
description: Wrap up and save session progress. Updates Current Project State in hospital-memory.md and logs the session in AGENTS.md.
---

# Workflow: Save Session Progress

When this command is invoked:
1. Review all code changes, tests, and features implemented during the current session.
2. Update the `## 4. الحالة الحالية للمشروع (Current Project State)` section in `hospital-memory.md`.
3. Add a concise session entry under `## Session log` in `AGENTS.md`.
4. Run `python -m graphify update .` to ensure knowledge graph is synced.
5. Provide a clear completion report to the user confirming everything is saved.
