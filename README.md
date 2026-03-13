# LicenseRadar 📡

**Find and eliminate wasted Microsoft 365 licenses. Free, open-source, self-hosted.**

![License](https://img.shields.io/badge/License-Apache_2.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4.svg?logo=php&logoColor=white)
![Microsoft 365](https://img.shields.io/badge/Microsoft_365-Graph_API-0078D4.svg?logo=microsoft&logoColor=white)
![Status](https://img.shields.io/badge/Status-In_Development-f59e0b.svg)
![WCAG](https://img.shields.io/badge/WCAG-2.2_AA-2E7D32.svg)
![2FA](https://img.shields.io/badge/2FA-Email_%7C_TOTP_%7C_Passkey-22c55e.svg)
![Stars](https://img.shields.io/github/stars/boopathirbk/licenseradar?style=social)

> **LicenseRadar** connects to your Microsoft 365 tenant via the Microsoft Graph API and shows you exactly which licenses are wasted, who hasn't signed in, and how much money you can recover — all from a clean, self-hosted web dashboard.  
> No PowerShell. No SaaS subscription. No CLI. Just upload and audit.

---

## 🚧 Status

This project is currently under active development.  
Star the repo to follow progress and get notified on release.

| Milestone | Status |
|---|---|
| Project architecture & design | ✅ Complete |
| GitHub Pages showcase site | 🔄 In progress |
| PHP app — install wizard | 🔄 In progress |
| PHP app — Graph API integration | 🔄 In progress |
| PHP app — 2FA (Email OTP + TOTP + Passkey) | 🔄 In progress |
| PHP app — Dashboard & reports | 🔜 Coming soon |
| v1.0 public release | 🔜 Coming soon |

---

## ✨ What It Does

LicenseRadar scans your Microsoft 365 tenant and finds:

- **Inactive licensed users** — assigned licenses but no sign-in in 30/60/90/180 days
- **Blocked accounts with active licenses** — disabled Entra ID users still consuming paid seats
- **Unassigned license slots** — purchased seats that aren't assigned to anyone
- **Redundant license stacking** — users assigned overlapping plans (e.g. M365 E3 + standalone Exchange Online)
- **Cost savings estimate** — calculates monthly and annual waste in USD and INR

---

## 🖥️ Key Features

- **Zero PowerShell** — everything runs in a web browser
- **Self-hosted** — your data never leaves your server
- **Works on shared hosting** — Hostinger, cPanel, GoDaddy, Namecheap, LiteSpeed — no SSH needed
- **WordPress-style install** — fill in DB details, connect Azure, done in under 10 minutes
- **3-method 2FA** — Email OTP (default), TOTP app, or Passkey / WebAuthn
- **Geist design system** — clean Vercel-style UI with auto dark/light mode
- **WCAG 2.2 AA** — fully accessible, keyboard navigable, screen reader friendly
- **PDF & Excel export** — one-click savings report for management
- **100% free** — no subscription, no account, no tracking

---

## 🔐 Authentication

```
Email + Password
      ↓
2FA Challenge — choose one:
  ├── 📧 Email OTP    (default, zero setup)
  ├── 📱 TOTP App     (Google / Microsoft Authenticator, Authy)
  └── 🔑 Passkey      (Face ID / Touch ID / Windows Hello / YubiKey)
      ↓
Dashboard
```

---

## 📡 Graph API Scopes (read-only)

LicenseRadar **never writes** to your tenant. Three read-only application permissions:

| Permission | Purpose |
|---|---|
| `User.Read.All` | Read all users and sign-in activity |
| `Directory.Read.All` | Read tenant directory |
| `Organization.Read.All` | Read subscribed SKUs and license counts |

---

## 🗺️ Roadmap

**v1.0 — Launch**
- [x] Project architecture & documentation
- [ ] Install wizard (WordPress-style)
- [ ] Graph API integration
- [ ] Inactive / blocked / unassigned / redundant detection
- [ ] Cost calculator (USD + INR)
- [ ] PDF & Excel export
- [ ] Email OTP 2FA
- [ ] TOTP authenticator 2FA
- [ ] Passkey / WebAuthn
- [ ] GitHub Pages showcase site

**v1.1 — Notifications**
- [ ] Scheduled weekly email digest
- [ ] Slack & Microsoft Teams webhook alerts

**v1.2 — Monitoring**
- [ ] Entra App Secrets expiry monitor
- [ ] MFA coverage report

**v2.0 — Scale**
- [ ] Multi-tenant / MSP mode
- [ ] Docker image

---

## 🤝 Contributing

Contributions are welcome once v1.0 is released.  
Watch the repo or check [Issues](https://github.com/boopathirbk/licenseradar/issues) to see where help is needed.

---

## 📄 License

Apache License 2.0 — see [LICENSE](LICENSE) for details.

---

## 👨‍💻 Author

**Boopathi R** — Senior IT Admin · Web Developer · Microsoft 365 Specialist · Bengaluru, India

[![LinkedIn](https://img.shields.io/badge/LinkedIn-boopathirb-0A66C2?logo=linkedin&logoColor=white)](https://linkedin.com/in/boopathirb)
[![GitHub](https://img.shields.io/badge/GitHub-boopathirbk-181717?logo=github&logoColor=white)](https://github.com/boopathirbk)
[![Portfolio](https://img.shields.io/badge/Portfolio-boopathirbk.github.io-000000?logo=vercel&logoColor=white)](https://boopathirbk.github.io/bresume/)

---

<p align="center">Made with ♥ in Bengaluru, India</p>
