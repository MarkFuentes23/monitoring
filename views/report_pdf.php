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

// Calculate different time periods
$oneDayAgo = date('Y-m-d', strtotime('-1 day'));
$sevenDaysAgo = date('Y-m-d', strtotime('-7 days'));
$thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));

// Get daily stats (for detailed table)
$stmt = $conn->prepare(
    "SELECT DATE(created_at) AS log_date, 
            AVG(latency) AS avg_latency,
            MIN(latency) AS min_latency, 
            MAX(latency) AS max_latency,
            SUM(CASE WHEN status='offline' THEN 1 ELSE 0 END) AS offline_count,
            COUNT(*) AS total_checks
     FROM ping_logs
     WHERE ip_id = ? AND created_at >= ?
     GROUP BY DATE(created_at)
     ORDER BY log_date DESC"
);
$stmt->execute([$report_id, $thirtyDaysAgo]);
$dailyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate overall metrics for different periods
function calculateMetrics($conn, $ip_id, $startDate) {
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS total_checks,
                SUM(CASE WHEN status='offline' THEN 1 ELSE 0 END) AS offline_count,
                AVG(latency) AS avg_latency,
                MAX(latency) AS max_latency
         FROM ping_logs
         WHERE ip_id = ? AND created_at >= ?"
    );
    $stmt->execute([$ip_id, $startDate]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $uptime = $result['total_checks'] > 0 
            ? round(($result['total_checks'] - $result['offline_count']) / $result['total_checks'] * 100, 2)
            : 0;
            
    return [
        'uptime' => $uptime,
        'avg_latency' => round($result['avg_latency'], 2),
        'max_latency' => round($result['max_latency'], 2),
        'checks' => $result['total_checks'],
        'offline' => $result['offline_count']
    ];
}

$dailyMetrics = calculateMetrics($conn, $report_id, $oneDayAgo);
$weeklyMetrics = calculateMetrics($conn, $report_id, $sevenDaysAgo);
$monthlyMetrics = calculateMetrics($conn, $report_id, $thirtyDaysAgo);

// Get hourly data for today's chart
$stmt = $conn->prepare(
    "SELECT HOUR(created_at) AS hour,
            AVG(latency) AS avg_latency,
            SUM(CASE WHEN status='offline' THEN 1 ELSE 0 END) AS offline_count,
            COUNT(*) AS total_checks
     FROM ping_logs
     WHERE ip_id = ? AND DATE(created_at) = CURDATE()
     GROUP BY HOUR(created_at)
     ORDER BY hour ASC"
);
$stmt->execute([$report_id]);
$hourlyData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize TCPDF
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('IT Network Monitoring');
$pdf->SetAuthor('IT Department');
$pdf->SetTitle('Network Device Status Report');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(TRUE, 10);
$pdf->AddPage();

// Define colors
$colorOnline = '#4CAF50';
$colorOffline = '#F44336';
$colorWarning = '#FF9800';
$colorHeader = '#2c3e50';
$colorSubheader = '#34495e';

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
    .metrics-box {width: 30%; float: left; margin: 0 1.5%; text-align: center; border: 1px solid #ddd; padding: 10px;}
    .metrics-title {font-weight: bold; margin-bottom: 5px;}
    .metrics-value {font-size: 20pt; margin: 10px 0;}
    .clear {clear: both;}
</style>

<h1>Network Device Status Report</h1>
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
    <tr>
        <th>Monitoring Since</th>
        <td>' . date("F j, Y", strtotime($device['date'])) . '</td>
    </tr>
</table>

<div class="section">
    <h2>Performance Overview</h2>
</div>';

// Performance summary boxes
$html .= '
<div class="metrics-box">
    <div class="metrics-title">DAILY UPTIME</div>
    <div class="metrics-value ' . ($dailyMetrics['uptime'] >= 99.9 ? 'status-good' : ($dailyMetrics['uptime'] >= 95 ? 'status-warning' : 'status-critical')) . '">
        ' . $dailyMetrics['uptime'] . '%
    </div>
    <div>Avg: ' . $dailyMetrics['avg_latency'] . ' ms</div>
</div>

<div class="metrics-box">
    <div class="metrics-title">WEEKLY UPTIME</div>
    <div class="metrics-value ' . ($weeklyMetrics['uptime'] >= 99.9 ? 'status-good' : ($weeklyMetrics['uptime'] >= 95 ? 'status-warning' : 'status-critical')) . '">
        ' . $weeklyMetrics['uptime'] . '%
    </div>
    <div>Avg: ' . $weeklyMetrics['avg_latency'] . ' ms</div>
</div>

<div class="metrics-box">
    <div class="metrics-title">MONTHLY UPTIME</div>
    <div class="metrics-value ' . ($monthlyMetrics['uptime'] >= 99.9 ? 'status-good' : ($monthlyMetrics['uptime'] >= 95 ? 'status-warning' : 'status-critical')) . '">
        ' . $monthlyMetrics['uptime'] . '%
    </div>
    <div>Avg: ' . $monthlyMetrics['avg_latency'] . ' ms</div>
</div>

<div class="clear"></div>

<div class="section">
    <h2>Detailed Performance Stats (Last 30 Days)</h2>
</div>';

// Table for daily stats
$html .= '
<table class="stats">
    <tr>
        <th>Date</th>
        <th>Avg Latency</th>
        <th>Min</th>
        <th>Max</th>
        <th>Offline</th>
        <th>Checks</th>
        <th>Uptime %</th>
    </tr>';

foreach ($dailyStats as $day) {
    $uptime = $day['total_checks'] > 0 
            ? round(($day['total_checks'] - $day['offline_count']) / $day['total_checks'] * 100, 2)
            : 0;
            
    $uptimeClass = $uptime >= 99.9 ? 'status-good' : ($uptime >= 95 ? 'status-warning' : 'status-critical');
    
    $html .= '
    <tr>
        <td>' . date('M j, Y', strtotime($day['log_date'])) . '</td>
        <td>' . number_format($day['avg_latency'], 2) . ' ms</td>
        <td>' . number_format($day['min_latency'], 2) . ' ms</td>
        <td>' . number_format($day['max_latency'], 2) . ' ms</td>
        <td>' . $day['offline_count'] . '</td>
        <td>' . $day['total_checks'] . '</td>
        <td class="' . $uptimeClass . '">' . $uptime . '%</td>
    </tr>';
}

$html .= '</table>';

// Status summary
$html .= '
<div class="section">
    <h2>Status Summary</h2>
</div>';

if ($monthlyMetrics['uptime'] < 95) {
    $html .= '
    <div style="border-left: 5px solid #F44336; padding: 10px; background-color: #FFEBEE;">
        <strong>Critical Issue:</strong> Device uptime is below acceptable threshold. Recommend immediate investigation.
    </div>';
} elseif ($monthlyMetrics['uptime'] < 99) {
    $html .= '
    <div style="border-left: 5px solid #FF9800; padding: 10px; background-color: #FFF3E0;">
        <strong>Warning:</strong> Device experiencing intermittent connectivity issues. Schedule maintenance.
    </div>';
} else {
    $html .= '
    <div style="border-left: 5px solid #4CAF50; padding: 10px; background-color: #E8F5E9;">
        <strong>Good Status:</strong> Device operating within normal parameters.
    </div>';
}

// Add a section for outage incidents if any
if ($monthlyMetrics['offline'] > 0) {
    $html .= '
    <div class="section">
        <h2>Notable Incidents</h2>
    </div>';
    
    // Get most significant outages
    $stmt = $conn->prepare(
        "SELECT DATE(created_at) AS outage_date, 
                COUNT(*) AS consecutive_failures
         FROM ping_logs
         WHERE ip_id = ? AND status = 'offline' AND created_at >= ?
         GROUP BY DATE(created_at)
         HAVING consecutive_failures >= 3
         ORDER BY consecutive_failures DESC
         LIMIT 3"
    );
    $stmt->execute([$report_id, $thirtyDaysAgo]);
    $outages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($outages) > 0) {
        $html .= '<table class="stats">
            <tr>
                <th>Date</th>
                <th>Failed Checks</th>
                <th>Severity</th>
            </tr>';
            
        foreach ($outages as $outage) {
            $severity = $outage['consecutive_failures'] >= 10 ? 'Critical' : ($outage['consecutive_failures'] >= 5 ? 'Major' : 'Minor');
            $severityClass = $outage['consecutive_failures'] >= 10 ? 'status-critical' : ($outage['consecutive_failures'] >= 5 ? 'status-warning' : '');
            
            $html .= '
            <tr>
                <td>' . date('M j, Y', strtotime($outage['outage_date'])) . '</td>
                <td>' . $outage['consecutive_failures'] . '</td>
                <td class="' . $severityClass . '">' . $severity . '</td>
            </tr>';
        }
        
        $html .= '</table>';
    } else {
        $html .= '<p>No significant outages detected in the monitoring period.</p>';
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
$pdf->Output('network_device_report_' . $report_id . '.pdf', 'I');