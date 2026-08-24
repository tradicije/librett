# LibreTT repository instructions

- The project is in a stability-first refactor window: do not add features or complete placeholders unless explicitly requested.
- Preserve the public contracts documented in `docs/refactor/API_CONTRACT.md`.
- After every code change, update `changelog.md` and all affected README, user, architecture, test, and operational documentation.
- Run the relevant checks from `tools/refactor_audit.sh` and `docs/refactor/SMOKE_CHECKLIST.md`.
- Every completed handoff must include a proposed imperative-style commit message.
