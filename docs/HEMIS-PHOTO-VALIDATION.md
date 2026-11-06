# Photo repair validation

- Core regression: 117 checks passed.
- Employee import: 22 checks passed; active-teacher recovery remains unchanged.
- Photo transport/cache: 20 checks passed with mocked cURL and DNS. Covers same-host and CDN redirects, credential isolation, loop/private-destination rejection, TLS/HTTP errors, nested photo extraction, scheme-less/spaced URLs, and forced retry after failed caching.
- Browser: cached PNG works for admin and owner; unrelated/anonymous access denied. Teacher cannot use administrator diagnostics. Administrator sees the retry button and a simulated SSL error; missing-photo diagnostic uses the real local endpoint without an external fetch.
- Exact shipped installer: install/reinstall passed; corrupt/missing files rejected; saved API key, local configuration and SQLite preserved.
- Changed PHP files passed syntax checks.

No real employee data or university API credentials were used. The user's current HEMIS image-server failure is not confirmed from this environment. The new diagnostic reports its category on the user's computer without exposing tokens or response bodies.
