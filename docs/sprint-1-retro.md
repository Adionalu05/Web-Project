# Sprint 1 Retrospective
**Period:** 6 April – 12 April 2026  
**Facilitator:** Elguga  

---

## What Went Well

- Database schema was designed collaboratively before writing any PHP — prevented major refactors later
- Auth system (register/login/logout) was completed cleanly end-to-end with no regressions
- `api/handle.php` single-entry-point pattern was agreed on early and followed consistently
- GitHub issues were created before starting each task — made progress visible

## What Did Not Go Well

- PHPMailer setup was underestimated — took much longer than expected due to php.ini extension issues on Windows; moved to Sprint 2
- Dark mode was added late and deprioritised — missed Sprint 1
- Two bugs found during integration (login "Access Denied" port mismatch, upload form submitting as GET) — caught late because each person tested only their own module
- Branch naming was inconsistent in early commits

## Action Items for Sprint 2

1. Test cross-module flows as a team before marking an issue Done
2. Follow branch naming: `feature/[nr]-[description]`
3. PHPMailer setup is Sprint 2 priority — Adiona leads with Xhensila supporting
4. Dark mode assigned to Arsela in Sprint 2
