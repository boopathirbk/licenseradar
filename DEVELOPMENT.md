# LicenseRadar — Development Guide

> **Last updated:** 13 March 2026 · **Status:** Showcase site live, PHP app built (pending testing)

## Project Overview

LicenseRadar is a **free, open-source, self-hosted** Microsoft 365 license audit tool. It has two deliverables:

1. **Showcase Site** (React) — ✅ **LIVE** at [boopathirbk.github.io/licenseradar](https://boopathirbk.github.io/licenseradar/)
2. **PHP Application** (self-hosted audit tool) — 🟡 **BUILT** (needs `composer install` + hosting setup)

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

## PHP Application — BUILT

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
2. **Authentication** — Login + 3-method 2FA (Email OTP, TOTP, WebAuthn)
3. **Graph API Client** — Client credentials grant, token caching, pagination
4. **Audit Detectors:**
   - Inactive licensed users (30/60/90/180 day thresholds)
   - Blocked accounts with active licenses
   - Unassigned license seats
   - Redundant license stacking (overlapping plans)
5. **Cost Calculator** — USD + INR pricing, pre-loaded SKU prices
6. **Dashboard** — Summary tiles, doughnut chart (Chart.js 4.5), detail tables
7. **Reports** — One-click PDF and multi-sheet Excel export
8. **Settings** — Theme toggle, 2FA management, session policy
9. **Security** — PDO prepared statements, Argon2id passwords, CSRF tokens, CSP headers, .htaccess protection

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

## Standards & Compliance
- **WCAG 2.2 AA** — Focus appearance, target size, accessible auth, reduced motion
- **SEO** — Title tags, meta descriptions, JSON-LD, heading hierarchy, semantic HTML
- **Security** — CSP, HSTS, X-Frame-Options, SameSite cookies, rate limiting
- **Accessibility** — Skip links, ARIA landmarks, focus-visible, screen reader friendly

---

## Git Workflow
- Push via **GitHub Desktop** (no CLI git)
- Auto-deploy to GitHub Pages on push to `main`
- `.github/workflows/deploy.yml` handles build + deploy
- **Do NOT** commit or push via terminal — user handles this manually

---

## Author
**Boopathi R** — [Portfolio](https://boopathirbk.github.io/bresume/) · [GitHub](https://github.com/boopathirbk) · genius@duck.com
