<?php
ob_start();
require_once '../config/db.php';
require_once '../vendor/autoload.php';

requireLogin();

// Validate report ID
if (!isset($_GET['report']) || !is_numeric($_GET['report'])) {
    header('Location: dashboard.php?error=invalid_report');
    exit;
}

$report_id = (int)$_GET['report'];

// Fetch device information
$stmt = $conn->prepare("SELECT * FROM add_ip WHERE id = ?");
$stmt->execute([$report_id]);
$device = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$device) {
    header('Location: dashboard.php?error=device_not_found');
    exit;
}

// Calculate today's date
$today = date('Y-m-d');

// Get hourly stats for today
$stmt = $conn->prepare(
    "SELECT HOUR(created_at) AS log_hour, 
            AVG(latency) AS avg_latency,
            MIN(latency) AS min_latency, 
            MAX(latency) AS max_latency,
            SUM(CASE WHEN status='offline' THEN 1 ELSE 0 END) AS offline_count,
            COUNT(*) AS total_checks
     FROM ping_logs
     WHERE ip_id = ? AND DATE(created_at) = ?
     GROUP BY HOUR(created_at)
     ORDER BY log_hour ASC"
);
$stmt->execute([$report_id, $today]);
$hourlyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate overall daily metrics
$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total_checks,
            SUM(CASE WHEN status='offline' THEN 1 ELSE 0 END) AS offline_count,
            AVG(latency) AS avg_latency,
            MIN(latency) AS min_latency,
            MAX(latency) AS max_latency
     FROM ping_logs
     WHERE ip_id = ? AND DATE(created_at) = ?"
);
$stmt->execute([$report_id, $today]);
$dailyMetrics = $stmt->fetch(PDO::FETCH_ASSOC);

$uptime = $dailyMetrics['total_checks'] > 0 
        ? round(($dailyMetrics['total_checks'] - $dailyMetrics['offline_count']) / $dailyMetrics['total_checks'] * 100, 2)
        : 0;

// Initialize TCPDF
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('IT Network Monitoring');
$pdf->SetAuthor('IT Department');
$pdf->SetTitle('Daily Network Device Status Report');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(TRUE, 10);
$pdf->AddPage();

// Define colors
$colorOnline = '#4CAF50';
$colorOffline = '#F44336';
$colorWarning = '#FF9800';

// Report Header
$html = '
<style>
    h1 {color: #2c3e50; font-size: 20pt; text-align: center; margin-bottom: 5px;}
    h2 {color: #34495e; font-size: 14pt; margin-top: 5px; margin-bottom: 5px;}
    .subtitle {color: #7f8c8d; font-size: 10pt; text-align: center; margin-top: 0;}
    .section {background-color: #f5f5f5; padding: 5px; margin-top: 10px; border-left: 5px solid #3498db;}
    .status-good {color: #4CAF50; font-weight: bold;}
    .status-warning {color: #FF9800; font-weight: bold;}
    .status-critical {color: #F44336; font-weight: bold;}
    table {width: 100%; border-collapse: collapse; margin-top: 5px; margin-bottom: 15px;}
    table.stats th {background-color: #3498db; color: white; font-weight: bold; text-align: center;}
    table.stats td, table.stats th {border: 1px solid #bdc3c7; padding: 5px; text-align: center;}
    table.info td, table.info th {border: 1px solid #bdc3c7; padding: 5px;}
    table.info th {width: 30%; background-color: #ecf0f1; text-align: right;}
    .metrics-box {width: 94%; margin: 10px auto; text-align: center; border: 1px solid #ddd; padding: 10px;}
    .metrics-title {font-weight: bold; margin-bottom: 5px;}
    .metrics-value {font-size: 24pt; margin: 10px 0;}
    .clear {clear: both;}
</style>

<h1>Daily Network Device Status Report</h1>
<p class="subtitle">Generated on ' . date('F j, Y') . ' at ' . date('H:i') . '</p>

<div class="section">
    <h2>Device Information</h2>
</div>

<table class="info">
    <tr>
        <th>IP Address</th>
        <td><strong>' . htmlspecialchars($device['ip_address']) . '</strong></td>
    </tr>
    <tr>
        <th>Description</th>
        <td>' . htmlspecialchars($device['description']) . '</td>
    </tr>
    <tr>
        <th>Location</th>
        <td>' . htmlspecialchars($device['location']) . '</td>
    </tr>
    <tr>
        <th>Current Status</th>
        <td class="' . ($device['status'] == 'online' ? 'status-good' : 'status-critical') . '">' . 
            strtoupper($device['status']) . '</td>
    </tr>
    <tr>
        <th>Current Latency</th>
        <td>' . number_format($device['latency'], 2) . ' ms</td>
    </tr>
</table>

<div class="section">
    <h2>Daily Performance Summary</h2>
</div>';

// Daily metrics box
$html .= '
<div class="metrics-box">
    <div class="metrics-title">TODAY\'S UPTIME</div>
    <div class="metrics-value ' . ($uptime >= 99.9 ? 'status-good' : ($uptime >= 95 ? 'status-warning' : 'status-critical')) . '">
        ' . $uptime . '%
    </div>
    <div>Average Latency: ' . round($dailyMetrics['avg_latency'], 2) . ' ms</div>
    <div>Min Latency: ' . round($dailyMetrics['min_latency'], 2) . ' ms | Max Latency: ' . round($dailyMetrics['max_latency'], 2) . ' ms</div>
    <div>Total Checks: ' . $dailyMetrics['total_checks'] . ' | Failed Checks: ' . $dailyMetrics['offline_count'] . '</div>
</div>';

// Hourly stats table
$html .= '
<div class="section">
    <h2>Hourly Performance Statistics</h2>
</div>

<table class="stats">
    <tr>
        <th>Hour</th>
        <th>Avg Latency</th>
        <th>Min</th>
        <th>Max</th>
        <th>Offline</th>
        <th>Checks</th>
        <th>Uptime %</th>
    </tr>';

foreach ($hourlyStats as $hour) {
    $hourUptime = $hour['total_checks'] > 0 
            ? round(($hour['total_checks'] - $hour['offline_count']) / $hour['total_checks'] * 100, 2)
            : 0;
            
    $uptimeClass = $hourUptime >= 99.9 ? 'status-good' : ($hourUptime >= 95 ? 'status-warning' : 'status-critical');
    
    $hourFormatted = sprintf("%02d:00 - %02d:59", $hour['log_hour'], $hour['log_hour']);
    
    $html .= '
    <tr>
        <td>' . $hourFormatted . '</td>
        <td>' . number_format($hour['avg_latency'], 2) . ' ms</td>
        <td>' . number_format($hour['min_latency'], 2) . ' ms</td>
        <td>' . number_format($hour['max_latency'], 2) . ' ms</td>
        <td>' . $hour['offline_count'] . '</td>
        <td>' . $hour['total_checks'] . '</td>
        <td class="' . $uptimeClass . '">' . $hourUptime . '%</td>
    </tr>';
}

$html .= '</table>';

// Status summary
$html .= '
<div class="section">
    <h2>Today\'s Status Summary</h2>
</div>';

if ($uptime < 95) {
    $html .= '
    <div style="border-left: 5px solid #F44336; padding: 10px; background-color: #FFEBEE;">
        <strong>Critical Issue:</strong> Device experiencing significant downtime today. Recommend immediate investigation.
    </div>';
} elseif ($uptime < 99) {
    $html .= '
    <div style="border-left: 5px solid #FF9800; padding: 10px; background-color: #FFF3E0;">
        <strong>Warning:</strong> Device experiencing intermittent connectivity issues today. Monitor closely.
    </div>';
} else {
    $html .= '
    <div style="border-left: 5px solid #4CAF50; padding: 10px; background-color: #E8F5E9;">
        <strong>Good Status:</strong> Device operating normally today.
    </div>';
}

// Add incidents section if any outages today
if ($dailyMetrics['offline_count'] > 0) {
    $html .= '
    <div class="section">
        <h2>Today\'s Incidents</h2>
    </div>';
    
    // Get significant outage periods
    $stmt = $conn->prepare(
        "SELECT 
            created_at AS outage_time,
            latency
         FROM ping_logs
         WHERE ip_id = ? AND DATE(created_at) = ? AND status = 'offline'
         ORDER BY created_at ASC"
    );
    $stmt->execute([$report_id, $today]);
    $outages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($outages) > 0) {
        $html .= '<table class="stats">
            <tr>
                <th>Time</th>
                <th>Last Recorded Latency</th>
            </tr>';
            
        foreach ($outages as $outage) {
            $html .= '
            <tr>
                <td>' . date('H:i:s', strtotime($outage['outage_time'])) . '</td>
                <td>' . number_format($outage['latency'], 2) . ' ms</td>
            </tr>';
        }
        
        $html .= '</table>';
    }
}

// Add footer
$html .= '
<div style="position: absolute; bottom: 10mm; width: 100%; text-align: center; font-size: 8pt; color: #95a5a6;">
    Generated by IT Network Monitoring System • Internal Use Only<br>
    Page {PAGENO} of {nb}
</div>';

// Output HTML to PDF
$pdf->writeHTML($html, true, false, true, false, '');

// Set page numbers
$pdf->setFooterFont(Array('helvetica', '', 8));
$pdf->setFooterMargin(5);

// Output PDF
$pdf->Output('daily_network_report_' . $report_id . '.pdf', 'I');
?>