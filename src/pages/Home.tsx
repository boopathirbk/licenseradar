import React from 'react';
import { Link } from 'react-router-dom';
import { ICONS } from '../constants';

const Home: React.FC = () => {
    return (
        <div className="relative">
            {/* Background effects */}
            <div className="hero-glow absolute inset-0 pointer-events-none" aria-hidden="true" />
            <div className="dot-grid absolute inset-0 pointer-events-none" aria-hidden="true" />

            <div className="relative py-16 md:py-24 px-4 sm:px-6 lg:px-8 max-w-6xl mx-auto space-y-28">

                {/* ═══ Hero ═══ */}
                <section className="text-center space-y-8 animate-fade-up" aria-labelledby="hero-title">
                    <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-medium tracking-wide dark:bg-zinc-800/60 bg-zinc-100 dark:text-zinc-400 text-zinc-700 border dark:border-zinc-700/50 border-zinc-300">
                        <span className="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse-dot" aria-hidden="true" />
                        In Development · Free · Self-Hosted
                    </div>

                    <h1 id="hero-title" className="text-5xl sm:text-6xl md:text-7xl font-bold tracking-tighter">
                        <span className="dark:text-white text-zinc-900">License</span>
                        <span className="bg-gradient-to-r from-sky-400 via-cyan-400 to-indigo-500 bg-clip-text text-transparent">Radar</span>
                    </h1>

                    <p className="text-lg md:text-xl dark:text-zinc-400 text-zinc-600 max-w-2xl mx-auto leading-relaxed">
                        Find and eliminate wasted Microsoft 365 licenses. Free, open-source, self-hosted audit tool powered by Microsoft Graph API.
                    </p>

                    <div className="flex flex-col sm:flex-row justify-center gap-3 pt-2">
                        <Link to="/docs" className="group inline-flex items-center justify-center gap-2.5 px-7 py-3 bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 rounded-xl font-semibold text-sm shadow-lg shadow-black/10 dark:shadow-white/5 transition-all duration-300 hover:shadow-xl hover:scale-[1.02] active:scale-[0.98]">
                            <ICONS.BookOpen className="w-4 h-4 transition-transform group-hover:rotate-[-6deg]" /> Read the Docs
                        </Link>
                        <a href="https://github.com/boopathirbk/licenseradar" target="_blank" rel="noreferrer" className="inline-flex items-center justify-center gap-2.5 px-7 py-3 dark:bg-zinc-800/60 bg-zinc-100 dark:text-zinc-300 text-zinc-700 rounded-xl font-semibold text-sm border dark:border-zinc-700/50 border-zinc-300 transition-all duration-300 dark:hover:bg-zinc-800 hover:bg-zinc-200 active:scale-[0.98]">
                            <ICONS.Github className="w-4 h-4" /> View Source
                        </a>
                    </div>
                </section>

                {/* ═══ What It Finds ═══ */}
                <section aria-labelledby="finds-heading">
                    <div className="text-center mb-12">
                        <h2 id="finds-heading" className="text-3xl font-bold dark:text-white text-zinc-900 tracking-tight">What It Finds</h2>
                        <p className="mt-3 dark:text-zinc-400 text-zinc-600 text-base">Automatically scans your Microsoft 365 tenant and surfaces every type of license waste.</p>
                    </div>

                    <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-5">
                        {[
                            {
                                icon: <ICONS.Clock className="w-5 h-5 text-sky-500" />,
                                title: 'Inactive Licensed Users',
                                description: 'Users with licenses but no sign-in in 30, 60, 90, or 180 days — with exact timestamps.',
                                gradient: 'from-sky-500/10 to-transparent',
                            },
                            {
                                icon: <ICONS.UserX className="w-5 h-5 text-rose-500" />,
                                title: 'Blocked Accounts',
                                description: 'Disabled Entra ID users still consuming paid license seats — immediate action items.',
                                gradient: 'from-rose-500/10 to-transparent',
                            },
                            {
                                icon: <ICONS.Layers className="w-5 h-5 text-amber-500" />,
                                title: 'Unassigned Seats',
                                description: 'Purchased license slots with no user assigned — per-SKU breakdown of spare capacity.',
                                gradient: 'from-amber-500/10 to-transparent',
                            },
                            {
                                icon: <ICONS.Copy className="w-5 h-5 text-violet-500" />,
                                title: 'Redundant Stacking',
                                description: 'Users with overlapping plans — e.g., M365 E3 + standalone Exchange Online Plan 2.',
                                gradient: 'from-violet-500/10 to-transparent',
                            },
                        ].map((card, i) => (
                            <article key={i} className="group relative dark:bg-zinc-900/50 bg-white p-6 rounded-2xl border dark:border-zinc-800/60 border-zinc-200 transition-all duration-300 hover:dark:border-zinc-700/80 hover:border-zinc-300 hover:shadow-lg hover:shadow-black/5 dark:hover:shadow-none">
                                <div className={`absolute inset-0 bg-gradient-to-b ${card.gradient} rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none`} aria-hidden="true" />
                                <div className="relative">
                                    <div className="w-10 h-10 rounded-xl dark:bg-zinc-800 bg-zinc-100 flex items-center justify-center mb-4 transition-transform duration-300 group-hover:scale-110">
                                        {card.icon}
                                    </div>
                                    <h3 className="text-sm font-semibold dark:text-white text-zinc-900 mb-2 tracking-tight">{card.title}</h3>
                                    <p className="text-sm dark:text-zinc-400 text-zinc-600 leading-relaxed">{card.description}</p>
                                </div>
                            </article>
                        ))}
                    </div>
                </section>

                {/* ═══ Key Features ═══ */}
                <section aria-labelledby="features-heading">
                    <div className="text-center mb-12">
                        <h2 id="features-heading" className="text-3xl font-bold dark:text-white text-zinc-900 tracking-tight">Key Features</h2>
                        <p className="mt-3 dark:text-zinc-400 text-zinc-600 text-base">Built for IT admins who need a free, private, production-ready audit tool.</p>
                    </div>

                    <div className="grid sm:grid-cols-2 gap-5">
                        {[
                            { icon: <ICONS.Globe className="w-4 h-4" />, title: 'Zero PowerShell', desc: 'Everything runs in a web browser. No scripts, no terminal, no technical knowledge needed.' },
                            { icon: <ICONS.Server className="w-4 h-4" />, title: 'Self-Hosted', desc: 'Your data never leaves your server. Works on shared hosting — Hostinger, cPanel, GoDaddy.' },
                            { icon: <ICONS.DollarSign className="w-4 h-4" />, title: '100% Free', desc: 'No subscription, no account, no tracking. Apache 2.0 licensed. Free forever.' },
                            { icon: <ICONS.Fingerprint className="w-4 h-4" />, title: '3-Method 2FA', desc: 'Email OTP, TOTP authenticator app, or Passkey / WebAuthn. All three built in.' },
                            { icon: <ICONS.FileText className="w-4 h-4" />, title: 'PDF & Excel Export', desc: 'One-click savings report for management. Formatted PDF and multi-sheet .xlsx.' },
                            { icon: <ICONS.PieChart className="w-4 h-4" />, title: 'Cost Calculator', desc: 'Monthly and annual waste in USD and INR. Pre-loaded pricing with custom override.' },
                            { icon: <ICONS.Eye className="w-4 h-4" />, title: 'WCAG 2.2 AA', desc: 'Fully accessible. Keyboard navigable, screen reader friendly, high contrast.' },
                            { icon: <ICONS.Moon className="w-4 h-4" />, title: 'Dark & Light Mode', desc: 'Auto-detects system preference. Manual toggle with Geist design system tokens.' },
                        ].map((item, i) => (
                            <div key={i} className="flex gap-4 p-5 rounded-xl dark:bg-zinc-900/30 bg-zinc-50 border dark:border-zinc-800/40 border-zinc-200 transition-colors hover:dark:border-zinc-700/60 hover:border-zinc-300">
                                <div className="w-9 h-9 rounded-lg dark:bg-zinc-800 bg-white flex items-center justify-center shrink-0 dark:text-sky-400 text-sky-600 border dark:border-zinc-700/50 border-zinc-200" aria-hidden="true">
                                    {item.icon}
                                </div>
                                <div>
                                    <h3 className="text-sm font-semibold dark:text-white text-zinc-900 mb-1">{item.title}</h3>
                                    <p className="text-sm dark:text-zinc-400 text-zinc-600 leading-relaxed">{item.desc}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                </section>

                {/* ═══ Comparison Table ═══ */}
                <section aria-labelledby="compare-heading">
                    <div className="text-center mb-12">
                        <h2 id="compare-heading" className="text-3xl font-bold dark:text-white text-zinc-900 tracking-tight">The Free Alternative</h2>
                        <p className="mt-3 dark:text-zinc-400 text-zinc-600 text-base">See how LicenseRadar compares to paid audit tools and PowerShell scripts.</p>
                    </div>

                    <div className="overflow-x-auto rounded-2xl border dark:border-zinc-800/60 border-zinc-200">
                        <table className="w-full text-sm" role="table">
                            <caption className="sr-only">Feature comparison between LicenseRadar and alternatives</caption>
                            <thead>
                                <tr className="dark:bg-zinc-900/60 bg-zinc-50">
                                    <th scope="col" className="text-left px-5 py-3.5 font-semibold dark:text-zinc-300 text-zinc-700 border-b dark:border-zinc-800/60 border-zinc-200">Feature</th>
                                    <th scope="col" className="text-center px-5 py-3.5 font-semibold text-sky-500 border-b dark:border-zinc-800/60 border-zinc-200">LicenseRadar</th>
                                    <th scope="col" className="text-center px-5 py-3.5 font-semibold dark:text-zinc-400 text-zinc-600 border-b dark:border-zinc-800/60 border-zinc-200">AdminDroid</th>
                                    <th scope="col" className="text-center px-5 py-3.5 font-semibold dark:text-zinc-400 text-zinc-600 border-b dark:border-zinc-800/60 border-zinc-200">PowerShell</th>
                                </tr>
                            </thead>
                            <tbody className="dark:text-zinc-400 text-zinc-600">
                                {[
                                    ['Price', '🟢 Free forever', '🔴 $595+ / year', '🟢 Free'],
                                    ['Self-hosted', '🟢 Yes', '🔴 Cloud SaaS', '🟡 Local only'],
                                    ['Web dashboard', '🟢 Yes', '🟢 Yes', '🔴 No'],
                                    ['No PowerShell needed', '🟢 Yes', '🟢 Yes', '🔴 Required'],
                                    ['Shared hosting', '🟢 Yes', '🔴 No', '🔴 No'],
                                    ['PDF / Excel export', '🟢 Yes', '🟢 Yes', '🟡 Manual'],
                                    ['2FA / Passkeys', '🟢 Yes', '🟡 SSO only', '🔴 No'],
                                    ['Open source', '🟢 Apache 2.0', '🔴 No', '🟡 Scripts vary'],
                                ].map(([feature, radar, admin, ps], i) => (
                                    <tr key={i} className={`border-b last:border-b-0 dark:border-zinc-800/40 border-zinc-200 ${i % 2 === 0 ? 'dark:bg-zinc-950 bg-white' : 'dark:bg-zinc-900/20 bg-zinc-50/50'}`}>
                                        <td className="px-5 py-3 font-medium dark:text-zinc-300 text-zinc-700">{feature}</td>
                                        <td className="px-5 py-3 text-center">{radar}</td>
                                        <td className="px-5 py-3 text-center">{admin}</td>
                                        <td className="px-5 py-3 text-center">{ps}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </section>

                {/* ═══ How It Works ═══ */}
                <section aria-labelledby="how-heading">
                    <div className="text-center mb-12">
                        <h2 id="how-heading" className="text-3xl font-bold dark:text-white text-zinc-900 tracking-tight">How It Works</h2>
                        <p className="mt-3 dark:text-zinc-400 text-zinc-600 text-base">Three steps to your first audit. Under 10 minutes.</p>
                    </div>

                    <div className="grid md:grid-cols-3 gap-6">
                        {[
                            {
                                step: '01',
                                icon: <ICONS.Key className="w-5 h-5 text-sky-500" />,
                                title: 'Register Azure App',
                                desc: 'Create an App Registration in Entra ID with 3 read-only Graph API permissions. No write access ever.',
                            },
                            {
                                step: '02',
                                icon: <ICONS.Download className="w-5 h-5 text-emerald-500" />,
                                title: 'Upload & Install',
                                desc: 'Download the ZIP, upload to your shared hosting, run the WordPress-style setup wizard. No SSH needed.',
                            },
                            {
                                step: '03',
                                icon: <ICONS.BarChart3 className="w-5 h-5 text-violet-500" />,
                                title: 'Audit & Save',
                                desc: 'Dashboard shows every wasted license with cost savings. Export PDF for your CFO in one click.',
                            },
                        ].map((item, i) => (
                            <div key={i} className="relative text-center p-7 rounded-2xl dark:bg-zinc-900/40 bg-white border dark:border-zinc-800/50 border-zinc-200 transition-all duration-300 hover:dark:border-zinc-700/60 hover:border-zinc-300 hover:shadow-lg hover:shadow-black/5 dark:hover:shadow-none">
                                <span className="absolute top-4 right-5 text-4xl font-black dark:text-zinc-800/80 text-zinc-100 select-none" aria-hidden="true">{item.step}</span>
                                <div className="w-12 h-12 rounded-xl dark:bg-zinc-800 bg-zinc-100 flex items-center justify-center mx-auto mb-5" aria-hidden="true">
                                    {item.icon}
                                </div>
                                <h3 className="text-base font-semibold dark:text-white text-zinc-900 mb-2 tracking-tight">{item.title}</h3>
                                <p className="text-sm dark:text-zinc-400 text-zinc-600 leading-relaxed">{item.desc}</p>
                            </div>
                        ))}
                    </div>
                </section>

                {/* ═══ Graph API Security ═══ */}
                <section aria-labelledby="api-heading">
                    <div className="text-center mb-10">
                        <h2 id="api-heading" className="text-3xl font-bold dark:text-white text-zinc-900 tracking-tight">Read-Only by Design</h2>
                        <p className="mt-3 dark:text-zinc-400 text-zinc-600 text-base">LicenseRadar never modifies your tenant. Three read-only permissions only.</p>
                    </div>

                    <div className="grid sm:grid-cols-3 gap-4">
                        {[
                            { perm: 'User.Read.All', desc: 'Read all users and sign-in activity' },
                            { perm: 'Directory.Read.All', desc: 'Read tenant directory' },
                            { perm: 'Organization.Read.All', desc: 'Read subscribed SKUs and license counts' },
                        ].map((item, i) => (
                            <div key={i} className="text-center p-5 rounded-2xl dark:bg-zinc-900/40 bg-white border dark:border-zinc-800/50 border-zinc-200">
                                <code className="text-sm font-mono font-semibold text-sky-500 block mb-2">{item.perm}</code>
                                <p className="text-xs dark:text-zinc-500 text-zinc-500 leading-relaxed">{item.desc}</p>
                            </div>
                        ))}
                    </div>
                </section>

                {/* ═══ CTA ═══ */}
                <section className="text-center py-8" aria-labelledby="cta-heading">
                    <div className="gradient-border rounded-2xl p-px">
                        <div className="dark:bg-zinc-900 bg-white rounded-2xl px-8 py-12 space-y-6">
                            <h2 id="cta-heading" className="text-2xl md:text-3xl font-bold dark:text-white text-zinc-900 tracking-tight">
                                Stop paying for licenses nobody uses
                            </h2>
                            <p className="dark:text-zinc-400 text-zinc-600 max-w-lg mx-auto">
                                Star the repo to follow progress. v1.0 is coming soon — with full audit, cost calculator, and one-click reports.
                            </p>
                            <div className="flex flex-col sm:flex-row justify-center gap-3">
                                <a href="https://github.com/boopathirbk/licenseradar" target="_blank" rel="noreferrer" className="group inline-flex items-center justify-center gap-2 px-6 py-3 bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 rounded-xl font-semibold text-sm transition-all duration-300 hover:scale-[1.02] active:scale-[0.98]">
                                    <ICONS.Star className="w-4 h-4" /> Star on GitHub
                                </a>
                                <Link to="/docs" className="inline-flex items-center justify-center gap-2 px-6 py-3 dark:bg-zinc-800/60 bg-zinc-100 dark:text-zinc-300 text-zinc-700 rounded-xl font-semibold text-sm border dark:border-zinc-700/50 border-zinc-300 transition-all duration-300 dark:hover:bg-zinc-800 hover:bg-zinc-200 active:scale-[0.98]">
                                    <ICONS.ArrowRight className="w-4 h-4" /> Setup Guide
                                </Link>
                            </div>
                        </div>
                    </div>
                </section>

            </div>
        </div>
    );
};

export default Home;
