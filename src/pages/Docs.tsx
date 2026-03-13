import React from 'react';
import { ICONS } from '../constants';

const Docs: React.FC = () => {
    return (
        <div className="relative">
            <div className="dot-grid absolute inset-0 pointer-events-none" aria-hidden="true" />

            <div className="relative py-16 md:py-20 px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto space-y-16">

                {/* Header */}
                <section className="text-center space-y-4 animate-fade-up" aria-labelledby="docs-title">
                    <h1 id="docs-title" className="text-4xl md:text-5xl font-bold tracking-tighter dark:text-white text-zinc-900">
                        Documentation
                    </h1>
                    <p className="text-lg dark:text-zinc-400 text-zinc-600 max-w-xl mx-auto">
                        Everything you need to install, configure, and run LicenseRadar on your own server.
                    </p>
                </section>

                {/* Quick Start */}
                <section aria-labelledby="quickstart-heading" className="space-y-6">
                    <h2 id="quickstart-heading" className="text-2xl font-bold dark:text-white text-zinc-900 tracking-tight flex items-center gap-3">
                        <ICONS.Zap className="w-5 h-5 text-amber-500" /> Quick Start
                    </h2>
                    <div className="space-y-4">
                        {[
                            { step: '1', title: 'Download', desc: 'Download licenseradar-v1.x.x.zip from GitHub Releases. This ZIP includes the vendor/ folder — no Composer needed on server.' },
                            { step: '2', title: 'Upload', desc: "Extract and upload all files to your shared hosting subdomain via FTP or File Manager (e.g., licenseradar.yourdomain.com)." },
                            { step: '3', title: 'Run Setup', desc: 'Visit your domain in a browser. The setup wizard auto-launches — enter DB details, Azure credentials, and create your admin account.' },
                            { step: '4', title: 'Audit', desc: 'Dashboard immediately shows your license waste. Export a PDF report for management in one click.' },
                        ].map((item) => (
                            <div key={item.step} className="flex gap-4 p-5 rounded-xl dark:bg-zinc-900/30 bg-zinc-50 border dark:border-zinc-800/40 border-zinc-200">
                                <div className="w-9 h-9 rounded-lg bg-sky-500/10 flex items-center justify-center shrink-0 text-sky-500 font-bold text-sm border border-sky-500/20">
                                    {item.step}
                                </div>
                                <div>
                                    <h3 className="text-sm font-semibold dark:text-white text-zinc-900 mb-1">{item.title}</h3>
                                    <p className="text-sm dark:text-zinc-400 text-zinc-600 leading-relaxed">{item.desc}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                </section>

                {/* Requirements */}
                <section aria-labelledby="req-heading" className="space-y-6">
                    <h2 id="req-heading" className="text-2xl font-bold dark:text-white text-zinc-900 tracking-tight flex items-center gap-3">
                        <ICONS.Server className="w-5 h-5 text-emerald-500" /> Requirements
                    </h2>
                    <div className="overflow-x-auto rounded-xl border dark:border-zinc-800/60 border-zinc-200">
                        <table className="w-full text-sm" role="table">
                            <caption className="sr-only">Server requirements for LicenseRadar</caption>
                            <thead>
                                <tr className="dark:bg-zinc-900/60 bg-zinc-50">
                                    <th scope="col" className="text-left px-5 py-3 font-semibold dark:text-zinc-300 text-zinc-700 border-b dark:border-zinc-800/60 border-zinc-200">Component</th>
                                    <th scope="col" className="text-left px-5 py-3 font-semibold dark:text-zinc-300 text-zinc-700 border-b dark:border-zinc-800/60 border-zinc-200">Requirement</th>
                                </tr>
                            </thead>
                            <tbody className="dark:text-zinc-400 text-zinc-600">
                                {[
                                    ['PHP', '8.2 or higher'],
                                    ['Database', 'MySQL 8.0+ or MariaDB 10.6+'],
                                    ['Web Server', 'Apache 2.4+ (shared hosting) or Nginx'],
                                    ['HTTPS', 'Required — SSL/TLS certificate'],
                                    ['SMTP', 'Required for Email OTP (port 587 or 465)'],
                                    ['Azure', 'Microsoft 365 tenant + Entra ID App Registration'],
                                ].map(([component, req], i) => (
                                    <tr key={i} className={`border-b last:border-b-0 dark:border-zinc-800/40 border-zinc-200 ${i % 2 === 0 ? 'dark:bg-zinc-950 bg-white' : 'dark:bg-zinc-900/20 bg-zinc-50/50'}`}>
                                        <td className="px-5 py-3 font-medium dark:text-zinc-300 text-zinc-700">{component}</td>
                                        <td className="px-5 py-3">{req}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </section>

                {/* Azure App Registration */}
                <section aria-labelledby="azure-heading" className="space-y-6">
                    <h2 id="azure-heading" className="text-2xl font-bold dark:text-white text-zinc-900 tracking-tight flex items-center gap-3">
                        <ICONS.Key className="w-5 h-5 text-sky-500" /> Azure App Registration
                    </h2>
                    <p className="dark:text-zinc-400 text-zinc-600 leading-relaxed">
                        LicenseRadar uses the <strong className="dark:text-zinc-200 text-zinc-800">client credentials grant</strong> to access your Microsoft 365 tenant via the Graph API. You need to register an application in Microsoft Entra ID (Azure AD) and grant three read-only permissions.
                    </p>

                    <div className="space-y-3">
                        {[
                            'Log in to the Azure Portal (portal.azure.com) with admin privileges',
                            'Navigate to Microsoft Entra ID → App registrations → New registration',
                            'Name the app "LicenseRadar" and select "Accounts in this organizational directory only"',
                            'Copy the Application (client) ID and Directory (tenant) ID from the Overview page',
                            'Go to Certificates & secrets → New client secret → copy the Value immediately',
                            'Go to API permissions → Add a permission → Microsoft Graph → Application permissions',
                            'Add: User.Read.All, Directory.Read.All, Organization.Read.All',
                            'Click "Grant admin consent" for your tenant',
                            'Enter the Tenant ID, Client ID, and Client Secret in the LicenseRadar setup wizard',
                        ].map((step, i) => (
                            <div key={i} className="flex gap-3 items-start">
                                <span className="w-6 h-6 rounded-full bg-sky-500/10 flex items-center justify-center shrink-0 text-sky-500 text-xs font-bold mt-0.5">{i + 1}</span>
                                <p className="text-sm dark:text-zinc-400 text-zinc-600 leading-relaxed">{step}</p>
                            </div>
                        ))}
                    </div>
                </section>

                {/* .env Reference */}
                <section aria-labelledby="env-heading" className="space-y-6">
                    <h2 id="env-heading" className="text-2xl font-bold dark:text-white text-zinc-900 tracking-tight flex items-center gap-3">
                        <ICONS.Settings className="w-5 h-5 text-violet-500" /> Environment Variables
                    </h2>
                    <p className="dark:text-zinc-400 text-zinc-600 leading-relaxed">
                        The setup wizard creates the <code className="font-mono text-sm dark:bg-zinc-800 bg-zinc-100 px-1.5 py-0.5 rounded">.env</code> file automatically. Here's the full reference:
                    </p>
                    <div className="rounded-xl border dark:border-zinc-800/60 border-zinc-200 overflow-hidden">
                        <pre className="p-5 text-sm font-mono dark:bg-zinc-900/60 bg-zinc-50 dark:text-zinc-300 text-zinc-700 overflow-x-auto leading-relaxed">
{`# Database
DB_HOST=localhost
DB_NAME=licenseradar
DB_USER=your_db_user
DB_PASS=your_db_password

# Azure / Microsoft Graph
AZURE_TENANT_ID=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
AZURE_CLIENT_ID=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
AZURE_CLIENT_SECRET=your_client_secret_value

# SMTP (for Email OTP)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=you@gmail.com
SMTP_PASS=your_app_password
SMTP_FROM=noreply@yourdomain.com
SMTP_FROM_NAME=LicenseRadar

# App
APP_URL=https://licenseradar.yourdomain.com
APP_DEBUG=false
SESSION_LIFETIME=30
SESSION_ABSOLUTE=480`}
                        </pre>
                    </div>
                </section>

                {/* Hosting Compatibility */}
                <section aria-labelledby="hosting-heading" className="space-y-6">
                    <h2 id="hosting-heading" className="text-2xl font-bold dark:text-white text-zinc-900 tracking-tight flex items-center gap-3">
                        <ICONS.Globe className="w-5 h-5 text-emerald-500" /> Hosting Compatibility
                    </h2>
                    <div className="grid sm:grid-cols-2 gap-4">
                        {[
                            { host: 'Hostinger (hPanel)', status: '✅ Full', note: 'LiteSpeed — .htaccess supported natively' },
                            { host: 'cPanel (Apache)', status: '✅ Full', note: 'Most common shared host setup' },
                            { host: 'GoDaddy', status: '✅ Full', note: 'Apache / LiteSpeed — mod_rewrite available' },
                            { host: 'Namecheap', status: '✅ Full', note: 'Standard .htaccess rewriting' },
                            { host: 'SiteGround', status: '✅ Full', note: 'Apache + LiteSpeed compatible' },
                            { host: 'Nginx VPS', status: '⚠️ Manual', note: 'Needs try_files directive — doc below' },
                        ].map((item, i) => (
                            <div key={i} className="p-4 rounded-xl dark:bg-zinc-900/30 bg-zinc-50 border dark:border-zinc-800/40 border-zinc-200">
                                <div className="flex items-center justify-between mb-1">
                                    <span className="text-sm font-semibold dark:text-white text-zinc-900">{item.host}</span>
                                    <span className="text-xs font-medium">{item.status}</span>
                                </div>
                                <p className="text-xs dark:text-zinc-500 text-zinc-500">{item.note}</p>
                            </div>
                        ))}
                    </div>
                </section>

                {/* Nginx Config */}
                <section aria-labelledby="nginx-heading" className="space-y-6">
                    <h2 id="nginx-heading" className="text-2xl font-bold dark:text-white text-zinc-900 tracking-tight flex items-center gap-3">
                        <ICONS.Terminal className="w-5 h-5 text-zinc-500" /> Nginx Configuration
                    </h2>
                    <div className="rounded-xl border dark:border-zinc-800/60 border-zinc-200 overflow-hidden">
                        <pre className="p-5 text-sm font-mono dark:bg-zinc-900/60 bg-zinc-50 dark:text-zinc-300 text-zinc-700 overflow-x-auto leading-relaxed">
{`server {
    listen 80;
    server_name licenseradar.yourdomain.com;
    root /var/www/licenseradar/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \\.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }

    # Block sensitive directories
    location ~* /(vendor|app|config|storage|database) {
        deny all;
    }
}`}
                        </pre>
                    </div>
                </section>

            </div>
        </div>
    );
};

export default Docs;
