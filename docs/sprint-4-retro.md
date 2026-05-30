# Sprint 4 Retrospective
**Period:** 12 May – 19 May 2026  
**Facilitator:** Elguga  

---

## What Went Well

- Perfect sprint — all 6 issues completed, zero bugs found
- User manual and deployment guide done on day one (action item from Sprint 3 retro delivered)
- Multi-device demo worked flawlessly in rehearsal and live presentation
- Albanian documentation was complete and accurate — professor did not flag translation issues
- Team presentation was well-structured; each member presented their own module
- Empty-state UI fix (Sprint 3 retro action item) was validated during demo rehearsal — no gaps found

## What Did Not Go Well

- Sprint 4 was lighter than previous sprints by design, but deployment guide still took longer than a single day due to Windows php.ini gotchas — budgeting was tight
- Some `.SQ_Docs/` files were produced near the sprint deadline; earlier translation would reduce last-minute pressure

## Overall Project Retrospective

### What Worked Well Across the Project

- Single entry point (`api/handle.php`) with `?action=X` routing kept backend consistent throughout
- Designing the database schema in Sprint 1 before writing PHP prevented major refactors
- GitHub Issues created before starting each task made progress visible; team never lost track of who owned what
- Graceful fallbacks (AI search without API key, busy_timeout without errors) improved robustness without complexity
- Professor meetings incorporated promptly — folder click, file icons, collapsible tree all came from professor feedback

### What Would Be Done Differently

- Sprint 2 was too long (3 weeks, 10 issues) — should have been split into two sprints from the start
- PHPMailer should have been prototyped in Sprint 1 even if not shipped — the Windows extension setup time was a surprise
- Multi-device testing should have been in the test checklist from Sprint 1, not discovered in Sprint 3

### Lessons Learned

1. Agree on architecture patterns (entry point, session strategy, redirect format) in Sprint 1 — changes later are expensive
2. Cross-module integration testing should happen before any issue is marked Done, not after
3. Documentation is a feature — allocating a full sprint for it produced better results than treating it as an afterthought
4. Keep sprint scope to 5–7 issues; predictability beats velocity

