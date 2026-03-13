import React from 'react';
import { ICONS } from '../constants';

interface ChangelogEntry {
    version: string;
    date: string;
    tag: 'upcoming' | 'planned';
    tagColor: string;
    changes: string[];
}

const entries: ChangelogEntry[] = [
    {
        version: 'v1.0.0',
        date: 'Coming Soon',
        tag: 'upcoming',
        tagColor: 'bg-amber-500/10 text-amber-500 border-amber-500/20',
        changes: [
            'Self-hosted PHP application with setup wizard',
            'Microsoft Graph API integration (client credentials)',
            'Dashboard: inactive users, blocked accounts, unassigned seats, redundant stacking',
            'Cost calculator with USD and INR pricing',
            'PDF and Excel report export',
            '3-method 2FA: Email OTP, TOTP, Passkey/WebAuthn',
            'Geist design system with dark/light mode',
            'WCAG 2.2 AA accessible interface',
            'Shared hosting compatible — no SSH required',
        ],
    },
    {
        version: 'v1.1.0',
        date: 'Planned',
        tag: 'planned',
        tagColor: 'bg-zinc-500/10 text-zinc-500 border-zinc-500/20',
        changes: [
            'Email alert when new waste is detected',
            'Scheduled auto-audit (daily/weekly cron)',
            'Webhook integration for Slack/Teams',
        ],
    },
    {
        version: 'v1.2.0',
        date: 'Planned',
        tag: 'planned',
        tagColor: 'bg-zinc-500/10 text-zinc-500 border-zinc-500/20',
        changes: [
            'License usage trend charts (30/60/90 day)',
            'Per-department waste breakdown',
            'Savings history dashboard',
        ],
    },
    {
        version: 'v2.0.0',
        date: 'Planned',
        tag: 'planned',
        tagColor: 'bg-zinc-500/10 text-zinc-500 border-zinc-500/20',
        changes: [
            'Multi-tenant support',
            'Role-based access control (RBAC)',
            'REST API for external integrations',
            'Docker container deployment option',
        ],
    },
];

const Changelog: React.FC = () => {
    return (
        <div className="relative">
            <div className="dot-grid absolute inset-0 pointer-events-none" aria-hidden="true" />

            <div className="relative py-16 md:py-20 px-4 sm:px-6 lg:px-8 max-w-3xl mx-auto space-y-16">

                {/* Header */}
                <section className="text-center space-y-4 animate-fade-up" aria-labelledby="log-title">
                    <h1 id="log-title" className="text-4xl md:text-5xl font-bold tracking-tighter dark:text-white text-zinc-900">
                        Changelog
                    </h1>
                    <p className="text-lg dark:text-zinc-400 text-zinc-600 max-w-xl mx-auto">
                        Roadmap and release history for LicenseRadar.
                    </p>
                </section>

                {/* Timeline */}
                <section aria-label="Version timeline" className="space-y-8">
                    {entries.map((entry, i) => (
                        <article key={i} className="relative pl-8 border-l-2 dark:border-zinc-800 border-zinc-200">
                            {/* Timeline dot */}
                            <div className={`absolute -left-[9px] top-1 w-4 h-4 rounded-full border-2 ${entry.tag === 'upcoming'
                                ? 'bg-amber-500 border-amber-500 animate-pulse-dot'
                                : 'dark:bg-zinc-800 bg-zinc-200 dark:border-zinc-700 border-zinc-300'
                                }`} aria-hidden="true" />

                            <div className="p-6 rounded-2xl dark:bg-zinc-900/40 bg-white border dark:border-zinc-800/50 border-zinc-200 space-y-4">
                                <div className="flex flex-wrap items-center gap-3">
                                    <h2 className="text-xl font-bold dark:text-white text-zinc-900 tracking-tight">{entry.version}</h2>
                                    <span className={`px-2.5 py-0.5 rounded-full text-xs font-semibold border ${entry.tagColor}`}>
                                        {entry.tag === 'upcoming' ? 'Upcoming' : 'Planned'}
                                    </span>
                                    <span className="text-xs dark:text-zinc-500 text-zinc-500 flex items-center gap-1">
                                        <ICONS.Calendar className="w-3 h-3" aria-hidden="true" /> {entry.date}
                                    </span>
                                </div>
                                <ul className="space-y-2">
                                    {entry.changes.map((change, j) => (
                                        <li key={j} className="flex items-start gap-2.5 text-sm dark:text-zinc-400 text-zinc-600">
                                            {entry.tag === 'upcoming'
                                                ? <ICONS.Zap className="w-3.5 h-3.5 text-amber-500 shrink-0 mt-0.5" aria-hidden="true" />
                                                : <ICONS.CheckCircle2 className="w-3.5 h-3.5 dark:text-zinc-600 text-zinc-400 shrink-0 mt-0.5" aria-hidden="true" />
                                            }
                                            <span>{change}</span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        </article>
                    ))}
                </section>

            </div>
        </div>
    );
};

export default Changelog;
