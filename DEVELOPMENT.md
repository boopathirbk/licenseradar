# LicenseRadar — Development Guide

> **Last updated:** 14 March 2026 · **Status:** Showcase site live, PHP app built & audited (security hardened, race conditions fixed)

## Project Overview

LicenseRadar is a **free, open-source, self-hosted** Microsoft 365 license audit tool. It has two deliverables:

1. **Showcase Site** (React) — ✅ **LIVE** at [boopathirbk.github.io/licenseradar](https://boopathirbk.github.io/licenseradar/)
2. **PHP Application** (self-hosted audit tool) — ✅ **BUILT & AUDITED** (2026 standards compliant)

---

## Showcase Site — COMPLETED

### Tech Stack
- **React 19.1** + **TypeScript 5.8** (strict) + **Vite 6.4**
- **TailwindCSS v4.1** — CSS-first config with `@theme` + `@custom-variant`
- **Lucide React 0.577** — icons via `ICONS` object in `constants.tsx`
- **react-router-dom 7.13** — `HashRouter` for GitHub Pages
- **Geist font** — self-hosted variable woff2 in `public/fonts/`
- **GitHub Actions** — auto-deploy on push to `main` via `JamesIves/github-pages-deploy-action`

### Key Design Decisions
- **Dark mode:** Class-based (`.dark` on `<html>`), NOT media-query. Required `@custom-variant dark (&:where(.dark, .dark *))` in `index.css` — TailwindCSS v4 defaults to media queries otherwise.
- **Routing:** HashRouter (`/#/path`) for GitHub Pages static hosting.
- **Fonts:** Font-face in `index.css`, preloaded in `index.html`, served from `/licenseradar/fonts/`.
- **Base path:** Vite `base: '/licenseradar/'` for GitHub Pages subdirectory.
- **SEO:** Full meta tags, OG/Twitter cards, JSON-LD `SoftwareApplication` schema in `index.html`.

### File Structure
```
src/
├── main.tsx              # React entry
├── App.tsx               # HashRouter + routes
├── constants.tsx         # ICONS object + NAV_LINKS
├── index.css             # TailwindCSS v4 design system
├── vite-env.d.ts         # Vite types
├── components/
│   ├── Layout.tsx        # Header, nav, footer, theme toggle
│   └── ScrollToTop.tsx   # Route change scroll fix
└── pages/
    ├── Home.tsx          # Hero, features, comparison, CTA
    ├── Docs.tsx          # Setup guide, Azure walkthrough
    ├── Security.tsx      # 2FA, Graph API, sessions
    ├── Changelog.tsx     # Timeline roadmap
    ├── Author.tsx        # Profile card, skills, certs
    └── Donate.tsx        # BMC/PayPal/GitHub Sponsors
```

### Patterns (matching WinLocksmith)
- **Layout:** Frosted header, skip-to-main link, ARIA landmarks, mobile hamburger menu
- **Theme:** `localStorage('licenseradar-theme')`, `prefers-color-scheme` fallback
- **GitHub stars:** Fetched via API, cached in `sessionStorage` (10 min TTL)
- **Header buttons:** Star (GitHub link) + Donate (yellow, Buy Me a Coffee branded)
- **Footer:** Docs, Security, Author, Donate, License, Source links

---

## PHP Application — BUILT & AUDITED

### Tech Stack
- **PHP 8.2+** (targeting shared hosting: Hostinger, cPanel, GoDaddy)
- **MySQL 8.0+ / MariaDB 10.6+**
- **No framework** — vanilla PHP with PDO, .htaccess routing
- **Composer packages:**
  - `guzzlehttp/guzzle` ^7.10 — HTTP client for Graph API
  - `phpmailer/phpmailer` ^7.0 — Email OTP
  - `spomky-labs/otphp` ^11.4 — TOTP authenticator
  - `web-auth/webauthn-lib` ^5.2 — Passkey/WebAuthn
  - `dompdf/dompdf` ^3.1 — PDF export
  - `phpoffice/phpspreadsheet` ^5.5 — Excel export

### Features
1. **Install Wizard** — WordPress-style setup (DB, Azure creds, admin account)
2. **Authentication** — Login + 3-method 2FA (Email OTP, TOTP, Passkey/WebAuthn)
   - Passkey: WebAuthn registration with `navigator.credentials.create()`, challenge verification, passkey list with Remove, browser compatibility check
3. **Graph API Client** — Client credentials grant, token caching, pagination
4. **Audit Detectors:**
   - Inactive licensed users (30/60/90/180 day thresholds)
   - Blocked accounts with active licenses
   - Unassigned license seats
   - Redundant license stacking (overlapping plans)
5. **Cost Calculator** — USD + INR pricing, pre-loaded SKU prices
6. **Dashboard** — Summary tiles, doughnut chart (Chart.js 4.5), detail tables
7. **Reports** — One-click PDF and multi-sheet Excel export
8. **Settings** — Theme toggle (inline, no reload), 2FA management (enable/disable all 3 methods), passkey registration + removal, session policy
9. **Security** — PDO prepared statements, Argon2id passwords, CSRF tokens (`hash_equals` timing-safe), CSP headers, SRI on CDN scripts
10. **Setup Wizard Security** — CSRF verification on all POST forms, standalone security headers, hardened session (HttpOnly, SameSite, strict_mode), no error message leaks
11. **Race Condition Hardening:**
    - **OTP Replay Prevention** — Atomic `UPDATE … WHERE code = ? AND expires > NOW()` with `rowCount()` check (not SELECT→compare→UPDATE)
    - **Rate Limit Bypass Prevention** — INSERT attempt before count check (not check-then-insert)
    - **TOTP Replay Prevention** — `last_used_code` column prevents same code reuse within 30s window
    - **Info Disclosure Prevention** — No exception messages leaked to client (generic errors only)
12. **2026 Audit Compliance:**
    - **PHP 8.4** — `strict_types`, no deprecated functions, typed returns
    - **WCAG 2.2 AA** — `:focus-visible` indicators, `prefers-reduced-motion`, target size ≥24px, `autocomplete` on identity fields, skip link, ARIA landmarks
    - **SRI** — Chart.js and qrcode-generator CDN scripts verified with `sha384` integrity hashes
    - **CSP** — `script-src 'self' cdn.jsdelivr.net`, `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, HSTS
    - **CSRF** — `_csrf_token` field with `hash_equals()` timing-safe comparison on all POST forms
    - **Session** — `HttpOnly`, `SameSite=Strict`, `Secure`, `use_strict_mode`, `use_only_cookies`, idle + absolute timeouts

### Graph API Permissions (Application, read-only)
- `User.Read.All` — user profiles + signInActivity
- `Directory.Read.All` — account enabled status
- `Organization.Read.All` — subscribedSkus (license counts)

### API Notes
- `signInActivity` requires Azure AD P1/P2 license on tenant
- Pagination: `$top=120` max per page (reduced from 999 in 2023), follow `@odata.nextLink`
- `signInActivity` data retained for max 30 days (non-premium) or 90 days (premium)
- Client credentials grant: `POST https://login.microsoftonline.com/{tenant}/oauth2/v2.0/token`

### Hosting Requirements
- PHP 8.2+ with `curl`, `mbstring`, `openssl`, `pdo_mysql`, `json`
- MySQL/MariaDB with `InnoDB` engine
- HTTPS required (SSL certificate)
- Apache with `mod_rewrite` OR Nginx with `try_files`
- SMTP access (port 587 or 465) for Email OTP

---

## Local Testing Setup

### Prerequisites (already done)
- ✅ PHP 8.4.11 installed (`C:\tools\php84\php.exe`)
- ✅ Composer 2.8.11 installed
- ✅ `composer install` completed (52 packages in `app/vendor/`)

### Remaining Steps
1. **Install XAMPP** from [apachefriends.org](https://www.apachefriends.org/) (Windows 8.2.12)
2. Open **XAMPP Control Panel** → Start **MySQL** only (Apache not needed)
3. Click **Admin** next to MySQL → opens **phpMyAdmin** at `http://localhost/phpmyadmin`
4. Create a new database: `licenseradar` (utf8mb4_unicode_ci collation)
5. Start PHP's built-in dev server:
   ```powershell
   php -S localhost:8080 -t "c:\Users\user\Documents\GitHub\licenseradar\app\public"
   ```
6. Open browser → `http://localhost:8080/setup.php`
7. Walk through the 5-step wizard:
   - **Step 1:** Requirements check (should all pass)
   - **Step 2:** Database → host: `localhost`, port: `3306`, name: `licenseradar`, user: `root`, pass: *(empty)*
   - **Step 3:** Azure credentials (tenant ID, client ID, client secret from Azure Portal)
   - **Step 4:** Admin account (username, email, password — min 12 chars)
   - **Step 5:** Done → redirects to login

---

## Standards & Compliance (2026 Audit)

### PHP 8.4
- `declare(strict_types=1)` on all PHP files
- `PASSWORD_ARGON2ID` for hashing (not deprecated bcrypt)
- `random_bytes()` / `random_int()` for crypto
- No `strtok()`, `md5()`, `sha1()` standalone (deprecated in 8.4)
- Typed properties and return types throughout

### WCAG 2.2 AA
- **2.4.7 Focus Visible** — `:focus-visible` outline (2px solid #38bdf8)
- **2.5.8 Target Size** — All buttons ≥ 24×24px minimum
- **2.3.3 Reduced Motion** — `@media (prefers-reduced-motion: reduce)`
- **1.3.5 Input Purpose** — `autocomplete` attributes on all identity fields
- **2.4.1 Bypass Blocks** — Skip-to-main link
- **4.1.2 Name/Role/Value** — `aria-label`, `aria-pressed`, `role` on controls

### Security (OWASP 2026)
- **SRI** — `integrity="sha384-..."` + `crossorigin="anonymous"` on Chart.js and qrcode-generator
- **CSP** — `Content-Security-Policy` with `script-src`, `style-src`, `font-src`, `img-src`, `connect-src`
- **CSRF** — `hash_equals()` timing-safe comparison, `_csrf_token` on all forms + fetch
- **XSS** — `htmlspecialchars(ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8')` via `e()` helper
- **SQLi** — Parameterized PDO queries, no string concatenation
- **Session** — `HttpOnly`, `SameSite=Strict`, `Secure`, `use_strict_mode`
- **Headers** — `X-Content-Type-Options`, `X-Frame-Options: DENY`, `Referrer-Policy`, `Permissions-Policy`, HSTS
- **Rate Limiting** — Insert-before-check pattern (race-condition proof), IP-based, configurable window/attempts
- **OTP Replay** — Atomic UPDATE with `rowCount()` — concurrent requests can't both succeed
- **TOTP Replay** — `last_used_code` column rejects same code within 30s time window
- **Open Redirect** — `HTTP_REFERER` validated against `base_url()` before redirect
- **Info Disclosure** — Generic error messages only; exception details logged server-side, never sent to client
- **Setup CSRF** — All 4 setup wizard forms have `csrf_field()` + server-side `csrf_verify()` on POST

### CSS Architecture
- Semantic text classes: `text-heading`, `text-body`, `text-muted`, `text-label`, `text-dimmed`
- `html.light` overrides for all classes (no inline PHP ternaries)
- `border-color-muted`, `--progress-inactive` CSS variable for light mode
- Zero inline PHP color styles in templates

---

## Git Workflow
- Push via **GitHub Desktop** (no CLI git)
- Auto-deploy to GitHub Pages on push to `main`
- `.github/workflows/deploy.yml` handles build + deploy
- **Do NOT** commit or push via terminal — user handles this manually

---

## Author
**Boopathi R** — [Portfolio](https://boopathirbk.github.io/bresume/) · [GitHub](https://github.com/boopathirbk) · genius@duck.com
