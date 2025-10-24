# Security Policy

Thank you for helping keep hypnose-stammtisch.de and its users safe.

This document explains how to report vulnerabilities, our disclosure
expectations, supported versions, and security best practices for contributors.

## Supported Versions

We provide security updates for the following branches/tags:

- main (actively developed)
- Latest released tag (until the next minor/patch release)

Older branches or unmaintained forks are not covered.

## Reporting a Vulnerability

If you believe you have found a security vulnerability, please report it
privately and responsibly.

- Preferred: Use GitHub “Report a vulnerability” (Security > Advisories) to
  create a private advisory:  
  https://github.com/Kink-Development-Group/hypnose-stammtisch.de/security/advisories/new
- Alternatively: Email the maintainers at: security@kink-development-group.org
  (PGP welcome; see “PGP Key” below)

Please include the following in your report:

- Affected component, version/commit, and environment (OS, runtime, browser)
- Impact and severity (what could an attacker achieve?)
- Steps to reproduce or proof‑of‑concept (POC)
- Any relevant logs, configs, or screenshots (redact secrets)
- Suggested remediation ideas, if available
- Your preference for credit (name/handle/company) or anonymity

We will acknowledge receipt within 72 hours and aim to provide a status update
within 7 days. We strive to coordinate a fix and release within 30 days for
high/critical issues, 60 days for medium, and 90 days for low, but timelines may
vary depending on complexity and ecosystem coordination.

Please do not open public issues for vulnerabilities.

## Scope

Reports in scope include, but are not limited to:

- Remote code execution, injection (SQL/NoSQL/ORM), XSS, CSRF, SSRF
- Authentication/authorization flaws, privilege escalation, IDOR
- Sensitive data exposure, insecure direct object access
- Deserialization issues, path traversal, directory/file inclusion
- Supply‑chain risks (dependency confusion, malicious packages)
- Misconfigurations in the default project setup (e.g., CSP, CORS)

Out of scope (unless impact can be demonstrated):

- Best‑practice recommendations without a security impact
- Rate limiting and spam without exploitability
- Denial of service from volumetric attacks requiring unrealistic resources
- Vulnerabilities in unsupported dependencies or environments
- Social engineering against maintainers or users

## Coordinated Disclosure

- We ask you to keep details private until a fix is available.
- We may request a short grace period after patch release to allow deployments.
- We will credit reporters in release notes/advisories unless you request
  anonymity.
- If we cannot resolve the issue in a reasonable time, we will communicate
  interim mitigations where possible.

## Public Advisories

Once fixed, we will publish a GitHub Security Advisory with:

- Affected versions
- Severity (CVSS vector/score)
- Impact and technical details
- Patches and workarounds
- Upgrade/migration instructions
- Acknowledgements

## Development and Operational Security

Contributors and maintainers should follow these practices:

- Secrets management
  - Never commit secrets; use environment variables or secret managers.
  - Rotate credentials on suspicion of compromise.
- Dependencies
  - Pin versions and use lockfiles.
  - Run `npm audit`, `yarn audit`, or equivalent regularly; upgrade promptly.
  - Prefer well‑maintained dependencies with security posture.
- Build and CI/CD
  - Enable branch protection and required reviews.
  - Use least‑privilege tokens; avoid long‑lived personal access tokens.
  - Verify third‑party actions by pinning to commit SHAs.
- App security
  - Enforce strict Content Security Policy (CSP) where applicable.
  - Validate and sanitize all inputs; encode outputs.
  - Use HTTPS everywhere; set HSTS; secure cookies (`HttpOnly`, `Secure`,
    `SameSite`).
  - Implement proper authz checks on server endpoints (no client‑only checks).
- Reporting
  - Treat bug reports that hint at security impact as potential vulns until
    triaged.
  - Escalate suspected incidents via the private reporting channels above.

## Security Testing

We welcome good‑faith testing within these boundaries:

- Do not exfiltrate data or modify content beyond what is necessary to
  demonstrate impact.
- Do not run automated scans against production that may degrade service.
- Do not access user data that isn’t yours; use demo or test accounts when
  possible.
- Comply with applicable laws and do not harm users or infrastructure.

If you discover exposed secrets, notify us immediately and do not attempt to use
them.

## Contact

- Security: info@hypnose-stammtisch.de
- Maintainers: open an issue for non‑security topics
- Emergencies: include “[URGENT]” in the email subject for critical impact

## Attribution

This policy is inspired by industry best practices and GitHub’s coordinated
disclosure guidelines.
