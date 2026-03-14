<?php
/**
 * LicenseRadar — Dashboard
 */

use function LicenseRadar\{e, csrf_field, format_currency};

$theme   = $_SESSION['theme'] ?? 'dark';
$isDark  = $theme === 'dark';
$results = $_SESSION['audit_results'] ?? null;
$auditTs = $_SESSION['audit_timestamp'] ?? null;

$pageTitle = 'Dashboard';
ob_start();
?>

<div class="max-w-6xl mx-auto px-4 sm:px-6 py-8 space-y-8">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-heading">Dashboard</h1>
            <?php if ($auditTs): ?>
                <p class="text-sm text-zinc-500 mt-1">Last audit: <time datetime="<?= e($auditTs) ?>"><?= e($auditTs) ?></time></p>
            <?php else: ?>
                <p class="text-sm text-zinc-500 mt-1">No audit data yet. Run your first audit to see license usage.</p>
            <?php endif; ?>
        </div>
        <div class="flex items-center gap-3" role="group" aria-label="Dashboard actions">
            <form method="POST" action="?route=audit" style="display:inline">
                <?= csrf_field() ?>
                <button type="submit" class="btn-primary" aria-label="Run a new license audit on your Microsoft 365 tenant">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Run Audit
                </button>
            </form>
            <?php if ($results): ?>
            <a href="?route=export/pdf" class="btn-secondary" aria-label="Export audit results as PDF report">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                Export PDF
            </a>
            <a href="?route=export/excel" class="btn-secondary" aria-label="Export audit results as Excel spreadsheet">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><path d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"/></svg>
                Export Excel
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($results): ?>
    <?php $summary = $results['summary']; ?>

    <!-- Summary Tiles -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="tile">
            <div class="tile-icon bg-sky-500/10 text-sky-500">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
            </div>
            <div class="tile-value"><?= number_format($summary['total_users']) ?></div>
            <div class="tile-label">Licensed Users</div>
        </div>
        <div class="tile">
            <div class="tile-icon bg-rose-500/10 text-rose-500">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0zM12 9v4M12 17h.01"/></svg>
            </div>
            <div class="tile-value"><?= number_format($summary['total_waste']) ?></div>
            <div class="tile-label">Waste Items</div>
        </div>
        <div class="tile">
            <div class="tile-icon bg-emerald-500/10 text-emerald-500">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
            </div>
            <div class="tile-value"><?= format_currency($summary['savings_usd']) ?></div>
            <div class="tile-label">Monthly Savings (USD)</div>
        </div>
        <div class="tile">
            <div class="tile-icon bg-amber-500/10 text-amber-500">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
            </div>
            <div class="tile-value"><?= format_currency($summary['savings_inr'], 'INR') ?></div>
            <div class="tile-label">Monthly Savings (INR)</div>
        </div>
    </div>

    <!-- Chart + Breakdown -->
    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Doughnut Chart -->
        <div class="card lg:col-span-1">
            <h2 class="card-title">Waste Breakdown</h2>
            <div class="relative" style="height: 240px;">
                <canvas id="waste-chart"></canvas>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const ctx = document.getElementById('waste-chart');
                    if (ctx && typeof Chart !== 'undefined') {
                        var legendColor = document.documentElement.classList.contains('light') ? '#71717a' : '#a1a1aa';
                        new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: ['Inactive', 'Blocked', 'Unassigned', 'Redundant'],
                                datasets: [{
                                    data: [
                                        <?= count($results['inactive']) ?>,
                                        <?= count($results['blocked']) ?>,
                                        <?= count(array_filter($results['unassigned'], fn($s) => $s['available'] > 0)) ?>,
                                        <?= count($results['redundant']) ?>
                                    ],
                                    backgroundColor: ['#38bdf8', '#fb7185', '#fbbf24', '#a78bfa'],
                                    borderWidth: 0
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                cutout: '70%',
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                        labels: {
                                            color: legendColor,
                                            padding: 12,
                                            usePointStyle: true,
                                            pointStyleWidth: 8,
                                            font: { size: 11, family: "'Geist', sans-serif" }
                                        }
                                    }
                                }
                            }
                        });
                    }
                });
            </script>
        </div>

        <!-- Detail Tables -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Inactive Users -->
            <?php if (!empty($results['inactive'])): ?>
            <div class="card">
                <h2 class="card-title">Inactive Licensed Users <span class="badge badge-sky"><?= count($results['inactive']) ?></span></h2>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead><tr><th>Name</th><th>Email</th><th>Last Sign-In</th><th>Licenses</th></tr></thead>
                        <tbody>
                        <?php foreach (array_slice($results['inactive'], 0, 20) as $u): ?>
                            <tr>
                                <td class="font-medium"><?= e($u['displayName']) ?></td>
                                <td><?= e($u['mail']) ?></td>
                                <td><?= e($u['lastSignIn']) ?></td>
                                <td><?= e((string)$u['licenseCount']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (count($results['inactive']) > 20): ?>
                        <p class="text-xs text-muted mt-2 px-1">Showing 20 of <?= count($results['inactive']) ?> — export for full list</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Blocked Accounts -->
            <?php if (!empty($results['blocked'])): ?>
            <div class="card">
                <h2 class="card-title">Blocked Accounts with Licenses <span class="badge badge-rose"><?= count($results['blocked']) ?></span></h2>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead><tr><th>Name</th><th>Email</th><th>Licenses</th></tr></thead>
                        <tbody>
                        <?php foreach (array_slice($results['blocked'], 0, 20) as $u): ?>
                            <tr>
                                <td class="font-medium"><?= e($u['displayName']) ?></td>
                                <td><?= e($u['mail']) ?></td>
                                <td><?= e((string)$u['licenseCount']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Unassigned Seats -->
            <?php if (!empty($results['unassigned'])): ?>
            <div class="card">
                <h2 class="card-title">Unassigned License Seats <span class="badge badge-amber"><?= count($results['unassigned']) ?></span></h2>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead><tr><th>SKU</th><th>Total</th><th>Used</th><th>Available</th></tr></thead>
                        <tbody>
                        <?php foreach ($results['unassigned'] as $s): ?>
                            <tr>
                                <td class="font-medium"><?= e($s['displayName']) ?></td>
                                <td><?= e((string)$s['total']) ?></td>
                                <td><?= e((string)$s['consumed']) ?></td>
                                <td class="font-semibold text-amber-500"><?= e((string)$s['available']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Redundant Stacking -->
            <?php if (!empty($results['redundant'])): ?>
            <div class="card">
                <h2 class="card-title">Redundant License Stacking <span class="badge badge-violet"><?= count($results['redundant']) ?></span></h2>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead><tr><th>Name</th><th>Email</th><th>Overlaps</th></tr></thead>
                        <tbody>
                        <?php foreach (array_slice($results['redundant'], 0, 20) as $u): ?>
                            <tr>
                                <td class="font-medium"><?= e($u['displayName']) ?></td>
                                <td><?= e($u['mail']) ?></td>
                                <td>
                                    <?php foreach ($u['overlaps'] as $family => $skus): ?>
                                        <span class="badge badge-violet text-xs"><?= ucfirst(e($family)) ?>: <?= count($skus) ?></span>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php else: ?>
    <!-- Empty State -->
    <div class="card text-center py-16 space-y-4">
        <div class="mx-auto empty-state-icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" focusable="false">
                <path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
        </div>
        <h2 class="text-lg font-semibold text-heading">No Audit Data</h2>
        <p class="text-sm text-zinc-500 max-w-sm mx-auto">
            Click <strong>Run Audit</strong> to scan your Microsoft 365 tenant for license waste.
        </p>
    </div>
    <?php endif; ?>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
