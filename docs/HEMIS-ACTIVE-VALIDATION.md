# HEMIS active import validation

- Core regression: 117 checks passed.
- HEMIS import fixtures: 22 checks passed, including 250-account recovery, pending-import activation, manual-block preservation, existing-role preservation, recovery to teacher, relative photo URLs, duplicate rows, and image-cache validation.
- Photo transport: 6 checks passed using mocked cURL/DNS, including same-host credentials, no credentials to CDN, TLS, pinned public DNS, and rejecting private external hosts.
- HTTP regression: 45 checks passed against an isolated fixture database.
- Browser recovery: automatic recovery is visible as active teacher; ordinary administrators cannot import employees.
- Organization browser: hierarchy assignment, imported photo/fallback, role-forgery rejection, document editing and unassigned-account restrictions passed.
- Photo browser: cached PNG delivered to admin and owner; unrelated/anonymous users denied; no-store response.
- Installer: exact ZIP installed and reinstalled; saved API key, SQLite and local configuration preserved; corrupt/missing package files rejected.

Only test accounts and image fixtures were used. Live university API and real employee photos were not accessed. No schema migration is added by this change.
