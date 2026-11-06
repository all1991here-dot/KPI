Public Mozilla CA certificates supplied by certifi. Downloaded unchanged on 2026-11-06.
Source: https://raw.githubusercontent.com/certifi/python-certifi/master/certifi/cacert.pem
SHA-256: 2ef52ad56b4803e264045c915e55c236cbfa045b84b81efefc218a19eb44bd2b
License: LICENSE.certifi (Mozilla Public License 2.0).
Used on Windows where portable PHP often has no configured CA file. Linux uses its normal trust store. KPI_CA_BUNDLE overrides both; a corporate CA must be supplied by the organization's administrator. TLS peer and hostname verification remain enabled. Update periodically from the official source; never substitute the session proxy certificate in delivered files.
