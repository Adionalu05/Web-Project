# Sprint 2 Retrospective
**Period:** 13 April – 2 May 2026  
**Facilitator:** Elguga  

---

## What Went Well

- All 10 planned issues completed — first 100% sprint
- PHPMailer was handled correctly this time: Adiona led with Xhensila supporting, exactly as planned in Sprint 1 retro
- Cross-module testing caught B-03 (share modal) and B-04 (folder query) before demo — action item from Sprint 1 retro paid off
- Branch naming was consistent throughout: every branch followed `feature/[nr]-[description]`
- AI search integration was cleanly isolated — absent API key causes graceful fallback, not a crash
- Legacy file removal reduced JS bundle size and eliminated dead code confusion

## What Did Not Go Well

- Sprint was 3 weeks long (13 April – 2 May) — longer than the target 1–2 weeks; scope was too large for one sprint
- Folder system and file sharing had overlapping DB interactions — caused a brief merge conflict on `api/handle.php`
- Dark mode was deprioritised again in the first week before Arsela picked it up; should have been started day one
- B-05 (multi-device SQLITE_BUSY) was found late — multi-device scenario was not in the test checklist until Sprint 3

## Action Items for Sprint 3

1. Split large features into smaller issues — target 5–6 issues per sprint max
2. Add multi-device test scenario to standard test checklist
3. Folder recursive CTE needs edge-case testing (deeply nested, empty folders, circular-prevention)
4. Begin documentation sprint: SRS, SDD, test report, user manual

