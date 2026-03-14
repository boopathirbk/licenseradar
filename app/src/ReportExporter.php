<?php
/**
 * LicenseRadar — Report Exporter
 * Generates PDF (via Dompdf) and Excel (via PhpSpreadsheet) reports.
 */

declare(strict_types=1);

namespace LicenseRadar;

use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill, Font};

final class ReportExporter
{
    /**
     * Export audit results as PDF and stream to browser.
     *
     * @param array<string, mixed> $results
     */
    public function exportPDF(array $results): void
    {
        $html = $this->buildPDFHTML($results);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'Helvetica');
        $options->set('isPhpEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'LicenseRadar-Report-' . date('Y-m-d') . '.pdf';
        audit_log('export_pdf', $filename);

        $dompdf->stream($filename, ['Attachment' => true]);
    }

    /**
     * Export audit results as Excel and stream to browser.
     *
     * @param array<string, mixed> $results
     */
    public function exportExcel(array $results): void
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('LicenseRadar')
            ->setTitle('License Audit Report')
            ->setSubject('Microsoft 365 License Waste Analysis');

        // ── Sheet 1: Summary ─────────────────────────────────────────
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Summary');
        $this->applyHeaderStyle($sheet, 'A1:B1');
        $sheet->setCellValue('A1', 'Metric');
        $sheet->setCellValue('B1', 'Value');

        $summary = $results['summary'] ?? [];
        $summaryData = [
            ['Total Licensed Users', $summary['total_users'] ?? 0],
            ['Total Waste Items', $summary['total_waste'] ?? 0],
            ['Monthly Savings (USD)', '$' . number_format($summary['savings_usd'] ?? 0, 2)],
            ['Monthly Savings (INR)', '₹' . number_format($summary['savings_inr'] ?? 0, 2)],
            ['Annual Savings (USD)', '$' . number_format(($summary['savings_usd'] ?? 0) * 12, 2)],
            ['Annual Savings (INR)', '₹' . number_format(($summary['savings_inr'] ?? 0) * 12, 2)],
            ['Inactive Threshold (days)', $results['threshold_days'] ?? 90],
            ['Report Date', date('Y-m-d H:i')],
        ];

        foreach ($summaryData as $i => $row) {
            $sheet->setCellValue('A' . ($i + 2), $row[0]);
            $sheet->setCellValue('B' . ($i + 2), $row[1]);
        }

        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);

        // ── Sheet 2: Inactive Users ──────────────────────────────────
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Inactive Users');
        $headers = ['Display Name', 'Email', 'Last Sign-In', 'License Count'];
        $this->writeSheet($sheet2, $headers, $results['inactive'] ?? [], ['displayName', 'mail', 'lastSignIn', 'licenseCount']);

        // ── Sheet 3: Blocked Accounts ────────────────────────────────
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Blocked Accounts');
        $headers = ['Display Name', 'Email', 'License Count'];
        $this->writeSheet($sheet3, $headers, $results['blocked'] ?? [], ['displayName', 'mail', 'licenseCount']);

        // ── Sheet 4: Unassigned Seats ────────────────────────────────
        $sheet4 = $spreadsheet->createSheet();
        $sheet4->setTitle('Unassigned Seats');
        $headers = ['SKU', 'Total', 'Consumed', 'Available'];
        $this->writeSheet($sheet4, $headers, $results['unassigned'] ?? [], ['displayName', 'total', 'consumed', 'available']);

        // ── Sheet 5: Redundant Stacking ──────────────────────────────
        $sheet5 = $spreadsheet->createSheet();
        $sheet5->setTitle('Redundant Stacking');
        $headers = ['Display Name', 'Email', 'Overlap Details'];
        $redundantData = [];
        foreach ($results['redundant'] ?? [] as $user) {
            $overlapStr = '';
            foreach ($user['overlaps'] ?? [] as $family => $skus) {
                $overlapStr .= ucfirst($family) . ': ' . count($skus) . ' overlapping; ';
            }
            $redundantData[] = ['displayName' => $user['displayName'], 'mail' => $user['mail'], 'overlaps' => rtrim($overlapStr, '; ')];
        }
        $this->writeSheet($sheet5, $headers, $redundantData, ['displayName', 'mail', 'overlaps']);

        // ── Output ───────────────────────────────────────────────────
        $filename = 'LicenseRadar-Report-' . date('Y-m-d') . '.xlsx';
        audit_log('export_excel', $filename);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }

    // ── Helpers ───────────────────────────────────────────────────────

    /**
     * @param array<string> $headers
     * @param array<int, array<string, mixed>> $data
     * @param array<string> $keys
     */
    private function writeSheet(mixed $sheet, array $headers, array $data, array $keys): void
    {
        $cols = range('A', chr(64 + count($headers)));
        foreach ($headers as $i => $h) {
            $sheet->setCellValue($cols[$i] . '1', $h);
        }
        $lastCol = $cols[count($headers) - 1];
        $this->applyHeaderStyle($sheet, "A1:{$lastCol}1");

        foreach ($data as $row => $item) {
            foreach ($keys as $col => $key) {
                $sheet->setCellValue($cols[$col] . ($row + 2), $item[$key] ?? '');
            }
        }

        foreach ($cols as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }
    }

    private function applyHeaderStyle(mixed $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '18181B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '3F3F46']]],
        ]);
    }

    /**
     * Build the HTML for the PDF report.
     *
     * @param array<string, mixed> $results
     */
    private function buildPDFHTML(array $results): string
    {
        $summary  = $results['summary'] ?? [];
        $date     = date('F j, Y');
        $threshold = $results['threshold_days'] ?? 90;

        $html = <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #18181b; margin: 40px; }
                h1 { font-size: 22px; color: #0ea5e9; margin-bottom: 4px; }
                h2 { font-size: 14px; color: #18181b; margin-top: 28px; border-bottom: 2px solid #e4e4e7; padding-bottom: 4px; }
                .subtitle { color: #71717a; font-size: 10px; margin-bottom: 24px; }
                table { width: 100%; border-collapse: collapse; margin-top: 8px; }
                th { background: #18181b; color: #fff; padding: 6px 10px; text-align: left; font-size: 10px; }
                td { padding: 5px 10px; border-bottom: 1px solid #e4e4e7; font-size: 10px; }
                tr:nth-child(even) td { background: #fafafa; }
                .summary-grid { display: table; width: 100%; margin-bottom: 16px; }
                .summary-item { display: table-cell; text-align: center; padding: 12px; background: #f4f4f5; border-radius: 8px; margin: 4px; }
                .summary-value { font-size: 20px; font-weight: 700; color: #18181b; }
                .summary-label { font-size: 9px; color: #71717a; margin-top: 2px; }
                .footer { margin-top: 32px; text-align: center; color: #a1a1aa; font-size: 9px; }
            </style>
        </head>
        <body>
            <h1>LicenseRadar</h1>
            <p class="subtitle">Microsoft 365 License Audit Report · {$date} · Inactive threshold: {$threshold} days</p>

            <table>
                <tr>
                    <th>Metric</th><th>Value</th>
                </tr>
                <tr><td>Total Licensed Users</td><td>{$summary['total_users']}</td></tr>
                <tr><td>Total Waste Items</td><td>{$summary['total_waste']}</td></tr>
                <tr><td>Monthly Savings (USD)</td><td>\${$this->fmt($summary['savings_usd'])}</td></tr>
                <tr><td>Monthly Savings (INR)</td><td>₹{$this->fmt($summary['savings_inr'])}</td></tr>
                <tr><td>Annual Savings (USD)</td><td>\${$this->fmt($summary['savings_usd'] * 12)}</td></tr>
                <tr><td>Annual Savings (INR)</td><td>₹{$this->fmt($summary['savings_inr'] * 12)}</td></tr>
            </table>
        HTML;

        // Inactive users table
        if (!empty($results['inactive'])) {
            $html .= '<h2>Inactive Licensed Users (' . count($results['inactive']) . ')</h2>';
            $html .= '<table><tr><th>Name</th><th>Email</th><th>Last Sign-In</th><th>Licenses</th></tr>';
            foreach ($results['inactive'] as $u) {
                $name  = htmlspecialchars($u['displayName'], ENT_QUOTES, 'UTF-8');
                $email = htmlspecialchars($u['mail'], ENT_QUOTES, 'UTF-8');
                $html .= "<tr><td>{$name}</td><td>{$email}</td><td>" . htmlspecialchars((string)($u['lastSignIn'] ?? ''), ENT_QUOTES, 'UTF-8') . "</td><td>" . htmlspecialchars((string)($u['licenseCount'] ?? 0), ENT_QUOTES, 'UTF-8') . "</td></tr>";
            }
            $html .= '</table>';
        }

        // Blocked accounts table
        if (!empty($results['blocked'])) {
            $html .= '<h2>Blocked Accounts with Licenses (' . count($results['blocked']) . ')</h2>';
            $html .= '<table><tr><th>Name</th><th>Email</th><th>Licenses</th></tr>';
            foreach ($results['blocked'] as $u) {
                $name  = htmlspecialchars($u['displayName'], ENT_QUOTES, 'UTF-8');
                $email = htmlspecialchars($u['mail'], ENT_QUOTES, 'UTF-8');
                $html .= "<tr><td>{$name}</td><td>{$email}</td><td>" . htmlspecialchars((string)($u['licenseCount'] ?? 0), ENT_QUOTES, 'UTF-8') . "</td></tr>";
            }
            $html .= '</table>';
        }

        // Unassigned seats
        if (!empty($results['unassigned'])) {
            $html .= '<h2>Unassigned License Seats (' . count($results['unassigned']) . ')</h2>';
            $html .= '<table><tr><th>SKU</th><th>Total</th><th>Consumed</th><th>Available</th></tr>';
            foreach ($results['unassigned'] as $s) {
                $name = htmlspecialchars($s['displayName'], ENT_QUOTES, 'UTF-8');
                $html .= "<tr><td>{$name}</td><td>" . htmlspecialchars((string)($s['total'] ?? 0), ENT_QUOTES, 'UTF-8') . "</td><td>" . htmlspecialchars((string)($s['consumed'] ?? 0), ENT_QUOTES, 'UTF-8') . "</td><td>" . htmlspecialchars((string)($s['available'] ?? 0), ENT_QUOTES, 'UTF-8') . "</td></tr>";
            }
            $html .= '</table>';
        }

        $html .= '<div class="footer">Generated by LicenseRadar · ' . htmlspecialchars(Config::get('APP_URL', ''), ENT_QUOTES, 'UTF-8') . '</div>';
        $html .= '</body></html>';

        return $html;
    }

    private function fmt(float $value): string
    {
        return number_format($value, 2);
    }
}
