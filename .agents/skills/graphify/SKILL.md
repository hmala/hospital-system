---
name: graphify
description: Build, query, navigate, and update graphify AST knowledge graph structures across codebase repositories.
---

# Graphify Skill

Use this skill to convert workspace codebases into navigable AST knowledge graphs, query nodes/subgraphs, and maintain graph sync after code modifications.

## Core Commands

1. **Update Graph**:
   - `graphify update .` (Incremental AST update after code edits)
   - `graphify .` (Full initial extraction)

2. **Query & Navigate Graph**:
   - `graphify query "<question>"`
   - `graphify path "<nodeA>" "<nodeB>"`
   - `graphify explain "<concept>"`

3. **Output Files**:
   - `graphify-out/graph.json`
   - `graphify-out/graph.html`
   - `graphify-out/GRAPH_REPORT.md`

## Agent Guidelines

- Run `graphify update .` whenever source files are added or modified in a turn.
- Prefer `graphify query` or `graphify explain` over scanning raw files when exploring deep system architecture.
