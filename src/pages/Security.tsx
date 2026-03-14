import React from 'react';
import { ICONS } from '../constants';

const Security: React.FC = () => {
    return (
        <div className="relative">
            <div className="dot-grid absolute inset-0 pointer-events-none" aria-hidden="true" />

            <div className="relative py-16 md:py-20 px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto space-y-16">

                {/* Header */}
                <section className="text-center space-y-4 animate-fade-up" aria-labelledby="sec-title">
                    <h1 id="sec-title" className="text-4xl md:text-5xl font-bold tracking-tighter dark:text-white text-zinc-900">
                        Security
                    </h1>
                    <p className="text-lg dark:text-zinc-400 text-zinc-600 max-w-xl mx-auto">
                        Every security measure built into LicenseRadar — from authentication to API access.
                    </p>
                </section>

                {/* 2FA Methods */}
                <section aria-labelledby="twofa-heading" className="space-y-6">
                    <h2 id="twofa-heading" className="text-2xl font-bold dark:text-white text-zinc-900 tracking-tight flex items-center gap-3">
                        <ICONS.Fingerprint className="w-5 h-5 text-violet-500" /> Three-Factor Authentication
                    </h2>
                    <p className="dark:text-zinc-400 text-zinc-600 leading-relaxed">
                        Every login requires a second factor. You choose which methods to enable — all three can coexist.
                    </p>
                    <div className="grid sm:grid-cols-3 gap-5">
                        {[
                            {
                                icon: <ICONS.Mail className="w-5 h-5 text-sky-500" />,
                                title: 'Email OTP',
                                desc: 'A 6-digit code sent via SMTP to your email. 5-minute expiry, rate-limited to 3/hour. Default fallback method.',
                                tech: 'PHPMailer 7 + SMTP',
                            },
                            {
                                icon: <ICONS.Smartphone className="w-5 h-5 text-emerald-500" />,
                                title: 'TOTP Authenticator',
                                desc: 'Compatible with Google Authenticator, Authy, or 1Password. QR code setup, 30-second window.',
                                tech: 'OTPHP 11.4 (RFC 6238)',
                            },
                            {
                                icon: <ICONS.Fingerprint className="w-5 h-5 text-violet-500" />,
                                title: 'Passkey / WebAuthn',
                                desc: 'Biometric or hardware key. WebAuthn Level 2+ compliant. Works with TouchID, FaceID, YubiKey.',
                                tech: 'webauthn-lib 5.2',
                            },
                        ].map((item, i) => (
                            <div key={i} className="p-6 rounded-2xl dark:bg-zinc-900/40 bg-white border dark:border-zinc-800/50 border-zinc-200">
                                <div className="w-11 h-11 rounded-xl dark:bg-zinc-800 bg-zinc-100 flex items-center justify-center mb-4" aria-hidden="true">
                                    {item.icon}
                                </div>
                                <h3 className="text-sm font-semibold dark:text-white text-zinc-900 mb-2">{item.title}</h3>
                                <p className="text-sm dark:text-zinc-400 text-zinc-600 leading-relaxed mb-3">{item.desc}</p>
                                <span className="inline-block text-xs font-mono dark:bg-zinc-800 bg-zinc-100 dark:text-zinc-500 text-zinc-500 px-2 py-1 rounded">{item.tech}</span>
                            </div>
                        ))}
                    </div>
                </section>

                {/* Graph API Access */}
                <section aria-labelledby="graph-heading" className="space-y-6">
                    <h2 id="graph-heading" className="text-2xl font-bold dark:text-white text-zinc-900 tracking-tight flex items-center gap-3">
                        <ICONS.Shield className="w-5 h-5 text-sky-500" /> Graph API Access Model
                    </h2>
                    <div className="p-6 rounded-2xl dark:bg-zinc-900/40 bg-white border dark:border-zinc-800/50 border-zinc-200 space-y-4">
                        <div className="flex flex-wrap gap-3">
                            <span className="px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-500/10 text-emerald-500 border border-emerald-500/20">Read-Only</span>
                            <span className="px-3 py-1.5 rounded-lg text-xs font-semibold bg-sky-500/10 text-sky-500 border border-sky-500/20">Application Permissions</span>
                            <span className="px-3 py-1.5 rounded-lg text-xs font-semibold bg-violet-500/10 text-violet-500 border border-violet-500/20">Client Credentials Grant</span>
                        </div>
                        <div className="space-y-2">
                            {[
                                { scope: 'User.Read.All', why: 'Read user profiles, license assignments, and signInActivity timestamps' },
                                { scope: 'Directory.Read.All', why: 'Read tenant directory objects and account enabled status' },
                                { scope: 'Organization.Read.All', why: 'Read subscribedSkus for license counts and plan details' },
                            ].map((item, i) => (
                                <div key={i} className="flex items-start gap-3 p-3 rounded-lg dark:bg-zinc-800/30 bg-zinc-50">
                                    <ICONS.CheckCircle2 className="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" aria-hidden="true" />
                                    <div>
                                        <code className="text-sm font-mono font-semibold text-sky-500">{item.scope}</code>
                                        <p className="text-xs dark:text-zinc-500 text-zinc-500 mt-0.5">{item.why}</p>
                                    </div>
                                </div>
                            ))}
                        </div>
                        <div className="flex items-start gap-3 p-3 rounded-lg dark:bg-rose-500/5 bg-rose-50 border dark:border-rose-500/10 border-rose-200">
                            <ICONS.XCircle className="w-4 h-4 text-rose-500 shrink-0 mt-0.5" aria-hidden="true" />
                            <p className="text-sm dark:text-zinc-400 text-zinc-600">
                                <strong className="dark:text-zinc-200 text-zinc-800">No write permissions.</strong> LicenseRadar cannot disable users, remove licenses, or modify any tenant data.
                            </p>
                        </div>
                    </div>
                </section>

                {/* Session Security */}
                <section aria-labelledby="session-heading" className="space-y-6">
                    <h2 id="session-heading" className="text-2xl font-bold dark:text-white text-zinc-900 tracking-tight flex items-center gap-3">
                        <ICONS.Lock className="w-5 h-5 text-emerald-500" /> Session & Cookie Security
                    </h2>
                    <div className="overflow-x-auto rounded-xl border dark:border-zinc-800/60 border-zinc-200">
                        <table className="w-full text-sm" role="table">
                            <caption className="sr-only">Session security policies</caption>
                            <thead>
                                <tr className="dark:bg-zinc-900/60 bg-zinc-50">
                                    <th scope="col" className="text-left px-5 py-3 font-semibold dark:text-zinc-300 text-zinc-700 border-b dark:border-zinc-800/60 border-zinc-200">Policy</th>
                                    <th scope="col" className="text-left px-5 py-3 font-semibold dark:text-zinc-300 text-zinc-700 border-b dark:border-zinc-800/60 border-zinc-200">Value</th>
                                </tr>
                            </thead>
                            <tbody className="dark:text-zinc-400 text-zinc-600">
                                {[
                                    ['Idle timeout', '30 minutes (configurable)'],
                                    ['Absolute timeout', '8 hours'],
                                    ['Session regeneration', 'On every login and privilege escalation'],
                                    ['Cookie: HttpOnly', '✅ Yes — no JavaScript access'],
                                    ['Cookie: Secure', '✅ Yes — HTTPS only'],
                                    ['Cookie: SameSite', 'Strict — no cross-site leakage'],
                                    ['Brute-force protection', '5 attempts → 15 min lockout'],
                                    ['IP binding', 'Session locked to originating IP'],
                                ].map(([policy, value], i) => (
                                    <tr key={i} className={`border-b last:border-b-0 dark:border-zinc-800/40 border-zinc-200 ${i % 2 === 0 ? 'dark:bg-zinc-950 bg-white' : 'dark:bg-zinc-900/20 bg-zinc-50/50'}`}>
                                        <td className="px-5 py-3 font-medium dark:text-zinc-300 text-zinc-700">{policy}</td>
                                        <td className="px-5 py-3">{value}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </section>

                {/* HTTP Security Headers */}
                <section aria-labelledby="headers-heading" className="space-y-6">
                    <h2 id="headers-heading" className="text-2xl font-bold dark:text-white text-zinc-900 tracking-tight flex items-center gap-3">
                        <ICONS.ShieldAlert className="w-5 h-5 text-amber-500" /> HTTP Security Headers
                    </h2>
                    <div className="rounded-xl border dark:border-zinc-800/60 border-zinc-200 overflow-hidden">
                        <pre className="p-5 text-sm font-mono dark:bg-zinc-900/60 bg-zinc-50 dark:text-zinc-300 text-zinc-700 overflow-x-auto leading-relaxed">
{`Content-Security-Policy: default-src 'self'; script-src 'self' cdn.jsdelivr.net; style-src 'self' 'unsafe-inline'; font-src 'self'; img-src 'self' data:; connect-src 'self' https://graph.microsoft.com https://login.microsoftonline.com;
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
Referrer-Policy: strict-origin-when-cross-origin
Strict-Transport-Security: max-age=31536000; includeSubDomains
Permissions-Policy: camera=(), microphone=(), geolocation=()
All CDN scripts include integrity="sha384-..." (Subresource Integrity)`}
                        </pre>
                    </div>
                </section>

                {/* Database Security */}
                <section aria-labelledby="db-heading" className="space-y-6">
                    <h2 id="db-heading" className="text-2xl font-bold dark:text-white text-zinc-900 tracking-tight flex items-center gap-3">
                        <ICONS.Database className="w-5 h-5 text-sky-500" /> Data Protection
                    </h2>
                    <div className="grid sm:grid-cols-2 gap-4">
                        {[
                            { title: 'SQL Injection', desc: 'PDO prepared statements with explicit parameter binding on every query.', icon: <ICONS.ShieldCheck className="w-4 h-4 text-emerald-500" /> },
                            { title: 'XSS Prevention', desc: 'htmlspecialchars() output encoding on every user-facing variable.', icon: <ICONS.ShieldCheck className="w-4 h-4 text-emerald-500" /> },
                            { title: 'CSRF Tokens', desc: 'Per-session CSRF tokens with timing-safe hash_equals() on every POST form and fetch.', icon: <ICONS.ShieldCheck className="w-4 h-4 text-emerald-500" /> },
                            { title: 'Password Storage', desc: 'password_hash() with PASSWORD_ARGON2ID (bcrypt fallback).', icon: <ICONS.ShieldCheck className="w-4 h-4 text-emerald-500" /> },
                            { title: 'SRI Protection', desc: 'All CDN scripts verified with sha384 integrity hashes + crossorigin="anonymous".', icon: <ICONS.ShieldCheck className="w-4 h-4 text-emerald-500" /> },
                            { title: 'WCAG 2.2 AA', desc: 'Focus-visible indicators, prefers-reduced-motion, 24px target size, autocomplete, skip links.', icon: <ICONS.ShieldCheck className="w-4 h-4 text-emerald-500" /> },
                            { title: 'Client Secret', desc: 'Stored server-side in .env — never exposed to frontend or logs.', icon: <ICONS.ShieldCheck className="w-4 h-4 text-emerald-500" /> },
                            { title: 'Audit Log', desc: 'Every login, export, and config change is timestamped with user + IP.', icon: <ICONS.ShieldCheck className="w-4 h-4 text-emerald-500" /> },
                        ].map((item, i) => (
                            <div key={i} className="flex gap-3 p-4 rounded-xl dark:bg-zinc-900/30 bg-zinc-50 border dark:border-zinc-800/40 border-zinc-200">
                                {item.icon}
                                <div>
                                    <h3 className="text-sm font-semibold dark:text-white text-zinc-900 mb-0.5">{item.title}</h3>
                                    <p className="text-xs dark:text-zinc-500 text-zinc-500 leading-relaxed">{item.desc}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                </section>

            </div>
        </div>
    );
};

export default Security;
