import React from 'react';
import { ICONS } from '../constants';

const Author: React.FC = () => {
    return (
        <div className="py-16 px-4 max-w-3xl mx-auto space-y-10">

            {/* Profile Card */}
            <section className="dark:bg-zinc-900/40 bg-white dark:border-zinc-800/40 border-zinc-200 border rounded-2xl p-8 md:p-10 text-center" aria-labelledby="author-name">

                {/* Avatar */}
                <div className="relative w-32 h-32 mx-auto mb-6">
                    <div className="absolute -inset-1 bg-gradient-to-br from-sky-500 via-indigo-500 to-emerald-500 rounded-full animate-pulse opacity-40 blur-md" aria-hidden="true" />
                    <img
                        src="https://avatars.githubusercontent.com/u/22997723?v=4"
                        alt="Boopathi R — Creator of LicenseRadar"
                        className="relative w-32 h-32 rounded-full border-4 dark:border-zinc-800 border-white shadow-xl object-cover"
                        loading="eager"
                        width={128}
                        height={128}
                    />
                </div>

                <h1 id="author-name" className="text-2xl font-bold dark:text-white text-zinc-900 tracking-tight">Boopathi R</h1>
                <p className="text-sm text-sky-600 dark:text-sky-400 font-medium mt-1">Creator of LicenseRadar</p>
                <p className="flex items-center justify-center gap-1.5 text-sm dark:text-zinc-400 text-zinc-600 mt-2">
                    <ICONS.MapPin className="w-3.5 h-3.5" aria-hidden="true" /> Bengaluru, India
                </p>

                {/* Social Links */}
                <div className="flex flex-wrap justify-center gap-2 mt-5">
                    <SocialLink href="https://boopathirbk.github.io/bresume/" icon={<ICONS.Globe className="w-4 h-4" />} label="Portfolio" />
                    <SocialLink href="https://www.linkedin.com/in/boopathirb" icon={<ICONS.Linkedin className="w-4 h-4" />} label="LinkedIn" />
                    <SocialLink href="https://github.com/boopathirbk" icon={<ICONS.Github className="w-4 h-4" />} label="GitHub" />
                    <SocialLink href="https://buymeacoffee.com/boopathirbk" icon={<ICONS.Coffee className="w-4 h-4" />} label="Buy me a coffee" />
                </div>
            </section>

            {/* About Me */}
            <section className="dark:bg-zinc-900/40 bg-white dark:border-zinc-800/40 border-zinc-200 border rounded-2xl p-8 md:p-10" aria-labelledby="about-heading">
                <h2 id="about-heading" className="text-lg font-bold dark:text-white text-zinc-900 mb-4 flex items-center gap-2">
                    <ICONS.BookOpen className="w-5 h-5 text-emerald-500" aria-hidden="true" /> About Me
                </h2>
                <div className="space-y-4 text-sm dark:text-zinc-400 text-zinc-700 leading-relaxed">
                    <p>
                        I'm a Computer Science Engineer, open-source enthusiast, and a firm believer that technology should empower everyone — not just enterprises with deep pockets. LicenseRadar was born from the frustration of seeing companies pay thousands for license audit tools when the data is freely available via the Microsoft Graph API.
                    </p>
                    <p>
                        Beyond code, I'm a nature lover who believes in protecting life and contributing positively to the planet. I find my best ideas on long walks, and I build tools that make the digital world safer and more accessible for everyone.
                    </p>
                    <p>
                        When I'm not building open-source tools, I lead a small IT team, design websites, and explore the frontiers of AI prompting. I'm passionate about cybersecurity, cloud infrastructure, and creating software that respects its users.
                    </p>
                </div>
            </section>

            {/* Skills */}
            <section className="dark:bg-zinc-900/40 bg-white dark:border-zinc-800/40 border-zinc-200 border rounded-2xl p-8 md:p-10" aria-labelledby="skills-heading">
                <h2 id="skills-heading" className="text-lg font-bold dark:text-white text-zinc-900 mb-4 flex items-center gap-2">
                    <ICONS.Zap className="w-5 h-5 text-amber-500" aria-hidden="true" /> Skills & Expertise
                </h2>
                <div className="flex flex-wrap gap-2">
                    {['IT Administration', 'Microsoft 365', 'Azure', 'Cyber Security', 'Networking', 'Linux'].map(s => <Badge key={s}>{s}</Badge>)}
                    {['WordPress', 'SEO', 'React', 'TypeScript', 'PHP', 'Web Development'].map(s => <Badge key={s} color="rose">{s}</Badge>)}
                    {['AI Prompting', 'Gemini', 'GPT', 'Claude', 'Open Source'].map(s => <Badge key={s} color="emerald">{s}</Badge>)}
                </div>
            </section>

            {/* Certifications */}
            <section className="dark:bg-zinc-900/40 bg-white dark:border-zinc-800/40 border-zinc-200 border rounded-2xl p-8 md:p-10" aria-labelledby="certs-heading">
                <h2 id="certs-heading" className="text-lg font-bold dark:text-white text-zinc-900 mb-4 flex items-center gap-2">
                    <ICONS.CheckCircle2 className="w-5 h-5 text-emerald-500" aria-hidden="true" /> Certifications
                </h2>
                <div className="grid sm:grid-cols-2 gap-2.5">
                    {[
                        'AWS Certified Cloud Practitioner',
                        'Microsoft Certified: Azure Fundamentals',
                        'Microsoft 365 Certified: Fundamentals',
                        'Linux Essentials: 010 (LPI)',
                    ].map(cert => (
                        <div key={cert} className="flex items-center gap-2.5 text-sm dark:text-zinc-300 text-zinc-700 p-3 dark:bg-zinc-800/30 bg-zinc-50 rounded-xl border dark:border-zinc-800/30 border-zinc-100">
                            <ICONS.Shield className="w-4 h-4 text-sky-500 shrink-0" aria-hidden="true" />
                            {cert}
                        </div>
                    ))}
                </div>
            </section>

            {/* Contact */}
            <section className="dark:bg-zinc-900/40 bg-white dark:border-zinc-800/40 border-zinc-200 border rounded-2xl p-8 md:p-10 text-center" aria-labelledby="contact-heading">
                <h2 id="contact-heading" className="text-lg font-bold dark:text-white text-zinc-900 mb-3 flex items-center justify-center gap-2">
                    <ICONS.Mail className="w-5 h-5 text-sky-500" aria-hidden="true" /> Get in Touch
                </h2>
                <a href="mailto:genius@duck.com" className="text-sky-600 dark:text-sky-400 hover:underline text-sm font-medium">
                    genius@duck.com
                </a>
                <p className="text-xs dark:text-zinc-500 text-zinc-500 mt-2 max-w-sm mx-auto leading-relaxed">
                    This is a privacy-focused, masked email address via <strong className="dark:text-zinc-400 text-zinc-600">DuckDuckGo Email Protection</strong>. If your message is genuine, I'll reply from my personal address.
                </p>
            </section>
        </div>
    );
};

const Badge = ({ children, color = 'sky' }: { children: string, color?: 'sky' | 'rose' | 'emerald' }) => {
    const colors = {
        sky: 'dark:bg-sky-500/10 bg-sky-50 dark:text-sky-400 text-sky-700 dark:border-sky-500/20 border-sky-200',
        rose: 'dark:bg-rose-500/10 bg-rose-50 dark:text-rose-400 text-rose-700 dark:border-rose-500/20 border-rose-200',
        emerald: 'dark:bg-emerald-500/10 bg-emerald-50 dark:text-emerald-400 text-emerald-700 dark:border-emerald-500/20 border-emerald-200',
    };
    return (
        <span className={`px-2.5 py-1 rounded-lg text-xs font-medium border ${colors[color]}`}>
            {children}
        </span>
    );
};

const SocialLink = ({ href, icon, label }: { href: string; icon: React.ReactNode; label: string }) => (
    <a href={href} target="_blank" rel="noopener noreferrer" className="flex items-center gap-2 px-3.5 py-2 dark:bg-zinc-800 bg-zinc-100 dark:hover:bg-zinc-700 hover:bg-zinc-200 rounded-xl text-sm dark:text-zinc-300 text-zinc-700 transition-all duration-200 border dark:border-zinc-700/50 border-zinc-200 hover:scale-[1.02]" aria-label={`Visit ${label}`}>
        {icon} {label}
    </a>
);

export default Author;
