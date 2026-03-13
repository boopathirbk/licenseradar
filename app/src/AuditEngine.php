<?php
/**
 * LicenseRadar — Audit Engine
 * Detects license waste: inactive users, blocked accounts, unassigned seats, redundant stacking.
 * Calculates cost savings in USD and INR.
 */

declare(strict_types=1);

namespace LicenseRadar;

final class AuditEngine
{
    private GraphClient $graph;
    private int $inactiveDays;

    public function __construct()
    {
        $this->graph = new GraphClient();

        // Get configurable inactive threshold (default 90 days)
        $setting = Database::fetchOne("SELECT setting_value FROM settings WHERE setting_key = 'inactive_days'");
        $this->inactiveDays = $setting ? (int) $setting['setting_value'] : 90;
    }

    /**
     * Run all audit detectors and return results.
     *
     * @return array{
     *     inactive: array<int, array<string, mixed>>,
     *     blocked: array<int, array<string, mixed>>,
     *     unassigned: array<int, array<string, mixed>>,
     *     redundant: array<int, array<string, mixed>>,
     *     skus: array<int, array<string, mixed>>,
     *     summary: array{total_users: int, total_waste: int, savings_usd: float, savings_inr: float},
     *     threshold_days: int
     * }
     */
    public function runAudit(): array
    {
        $users = $this->graph->getUsers();
        $skus  = $this->graph->getSubscribedSkus();

        $inactive  = $this->detectInactive($users);
        $blocked   = $this->detectBlocked($users);
        $unassigned = $this->detectUnassigned($skus);
        $redundant = $this->detectRedundant($users);

        // Calculate savings
        $allWasteUsers = array_merge($inactive, $blocked, $redundant);
        $savings = $this->calculateSavings($allWasteUsers);

        $totalWaste = count($inactive) + count($blocked) + count(array_filter($unassigned, fn($s) => $s['available'] > 0)) + count($redundant);

        return [
            'inactive'       => $inactive,
            'blocked'        => $blocked,
            'unassigned'     => $unassigned,
            'redundant'      => $redundant,
            'skus'           => $skus,
            'summary'        => [
                'total_users' => count($users),
                'total_waste' => $totalWaste,
                'savings_usd' => $savings['usd'],
                'savings_inr' => $savings['inr'],
            ],
            'threshold_days' => $this->inactiveDays,
        ];
    }

    /**
     * Detect users with licenses but no sign-in within threshold.
     *
     * @param array<int, array<string, mixed>> $users
     * @return array<int, array<string, mixed>>
     */
    private function detectInactive(array $users): array
    {
        $cutoff   = new \DateTimeImmutable("-{$this->inactiveDays} days", new \DateTimeZone('UTC'));
        $inactive = [];

        foreach ($users as $user) {
            if (!($user['accountEnabled'] ?? true)) {
                continue; // Skip blocked — handled separately
            }

            $licenses = $user['assignedLicenses'] ?? [];
            if (empty($licenses)) {
                continue;
            }

            $lastSignIn = $user['signInActivity']['lastSignInDateTime']
                       ?? $user['signInActivity']['lastNonInteractiveSignInDateTime']
                       ?? null;

            if ($lastSignIn === null) {
                // Never signed in but has licenses
                $inactive[] = [
                    'id'            => $user['id'],
                    'displayName'   => $user['displayName'] ?? '',
                    'mail'          => $user['mail'] ?? $user['userPrincipalName'] ?? '',
                    'lastSignIn'    => 'Never',
                    'licenseCount'  => count($licenses),
                    'licenseSkuIds' => array_column($licenses, 'skuId'),
                ];
                continue;
            }

            try {
                $signInDate = new \DateTimeImmutable($lastSignIn, new \DateTimeZone('UTC'));
                if ($signInDate < $cutoff) {
                    $inactive[] = [
                        'id'            => $user['id'],
                        'displayName'   => $user['displayName'] ?? '',
                        'mail'          => $user['mail'] ?? $user['userPrincipalName'] ?? '',
                        'lastSignIn'    => $signInDate->format('Y-m-d'),
                        'licenseCount'  => count($licenses),
                        'licenseSkuIds' => array_column($licenses, 'skuId'),
                    ];
                }
            } catch (\Throwable) {
                // Skip invalid dates
            }
        }

        return $inactive;
    }

    /**
     * Detect disabled/blocked accounts that still have licenses.
     *
     * @param array<int, array<string, mixed>> $users
     * @return array<int, array<string, mixed>>
     */
    private function detectBlocked(array $users): array
    {
        $blocked = [];

        foreach ($users as $user) {
            if (($user['accountEnabled'] ?? true) === true) {
                continue;
            }

            $licenses = $user['assignedLicenses'] ?? [];
            if (empty($licenses)) {
                continue;
            }

            $blocked[] = [
                'id'            => $user['id'],
                'displayName'   => $user['displayName'] ?? '',
                'mail'          => $user['mail'] ?? $user['userPrincipalName'] ?? '',
                'licenseCount'  => count($licenses),
                'licenseSkuIds' => array_column($licenses, 'skuId'),
            ];
        }

        return $blocked;
    }

    /**
     * Detect unassigned license seats from subscribed SKUs.
     *
     * @param array<int, array<string, mixed>> $skus
     * @return array<int, array<string, mixed>>
     */
    private function detectUnassigned(array $skus): array
    {
        $unassigned = [];

        foreach ($skus as $sku) {
            $enabled  = (int) ($sku['prepaidUnits']['enabled'] ?? 0);
            $consumed = (int) ($sku['consumedUnits'] ?? 0);
            $available = $enabled - $consumed;

            if ($available > 0) {
                $unassigned[] = [
                    'skuId'        => $sku['skuId'] ?? '',
                    'skuPartNumber' => $sku['skuPartNumber'] ?? '',
                    'displayName'  => $sku['skuPartNumber'] ?? '',
                    'total'        => $enabled,
                    'consumed'     => $consumed,
                    'available'    => $available,
                ];
            }
        }

        return $unassigned;
    }

    /**
     * Detect users with redundant license stacking (e.g., E3 + E5).
     *
     * @param array<int, array<string, mixed>> $users
     * @return array<int, array<string, mixed>>
     */
    private function detectRedundant(array $users): array
    {
        // SKU hierarchy — higher tier includes lower tier features
        $hierarchy = [
            // E-series
            '05e9a617-0261-4cee-bb36-b42a42367b1b' => 3, // M365 E5
            '06ebc4ee-1bb5-47dd-8120-11324bc54e06' => 2, // M365 E3
            '18181a46-0d4e-45cd-891e-60aabd171b4e' => 1, // O365 E1
            '6fd2c87f-b296-42f0-b197-1e91e994b900' => 2, // O365 E3
            'c7df2760-2c81-4ef7-bb8e-fb5e7aca4c1d' => 3, // O365 E5
            // Business series
            '4b585984-651b-4235-8c24-3b0f5e89f8e8' => 3, // Business Premium
            'f245ecc8-75af-4f8e-b61f-27d8114de5f3' => 2, // Business Standard
            'cbdc14ab-d96c-4c30-b9f4-6ada7cdc1d46' => 1, // Business Basic
            // Exchange
            '4ef96642-f096-40de-a3e9-d83fb2f90211' => 1, // Exchange Plan 1
            '19ec0d23-8335-4cbd-94ac-6050e5b3d6b2' => 2, // Exchange Plan 2
        ];

        // Group overlapping SKUs by product family
        $families = [
            'enterprise' => [
                '05e9a617-0261-4cee-bb36-b42a42367b1b',
                '06ebc4ee-1bb5-47dd-8120-11324bc54e06',
                '18181a46-0d4e-45cd-891e-60aabd171b4e',
                'c7df2760-2c81-4ef7-bb8e-fb5e7aca4c1d',
                '6fd2c87f-b296-42f0-b197-1e91e994b900',
            ],
            'business' => [
                '4b585984-651b-4235-8c24-3b0f5e89f8e8',
                'f245ecc8-75af-4f8e-b61f-27d8114de5f3',
                'cbdc14ab-d96c-4c30-b9f4-6ada7cdc1d46',
            ],
            'exchange' => [
                '4ef96642-f096-40de-a3e9-d83fb2f90211',
                '19ec0d23-8335-4cbd-94ac-6050e5b3d6b2',
            ],
        ];

        $redundant = [];

        foreach ($users as $user) {
            $licenses = $user['assignedLicenses'] ?? [];
            if (count($licenses) < 2) {
                continue;
            }

            $userSkuIds = array_column($licenses, 'skuId');
            $overlaps   = [];

            foreach ($families as $familyName => $familySkus) {
                $userFamilySkus = array_intersect($userSkuIds, $familySkus);
                if (count($userFamilySkus) >= 2) {
                    // User has multiple SKUs in the same family — redundant
                    $overlaps[$familyName] = $userFamilySkus;
                }
            }

            if (!empty($overlaps)) {
                $redundant[] = [
                    'id'          => $user['id'],
                    'displayName' => $user['displayName'] ?? '',
                    'mail'        => $user['mail'] ?? $user['userPrincipalName'] ?? '',
                    'overlaps'    => $overlaps,
                    'licenseSkuIds' => $userSkuIds,
                ];
            }
        }

        return $redundant;
    }

    /**
     * Calculate cost savings for wasted licenses.
     *
     * @param array<int, array<string, mixed>> $wasteUsers
     * @return array{usd: float, inr: float}
     */
    private function calculateSavings(array $wasteUsers): array
    {
        // Load pricing
        $pricing = Database::fetchAll('SELECT sku_id, price_usd, price_inr FROM sku_pricing');
        $priceMap = [];
        foreach ($pricing as $p) {
            $priceMap[$p['sku_id']] = ['usd' => (float) $p['price_usd'], 'inr' => (float) $p['price_inr']];
        }

        $totalUSD = 0.0;
        $totalINR = 0.0;

        foreach ($wasteUsers as $user) {
            $skuIds = $user['licenseSkuIds'] ?? [];
            foreach ($skuIds as $skuId) {
                if (isset($priceMap[$skuId])) {
                    $totalUSD += $priceMap[$skuId]['usd'];
                    $totalINR += $priceMap[$skuId]['inr'];
                }
            }
        }

        return ['usd' => round($totalUSD, 2), 'inr' => round($totalINR, 2)];
    }
}
