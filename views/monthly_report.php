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

// Calculate date range
$thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));
$today = date('Y-m-d');

// Get daily stats for last 30 days
$stmt = $conn->prepare(
    "SELECT DATE(created_at) AS log_date, 
            AVG(latency) AS avg_latency,
            MIN(latency) AS min_latency, 
            MAX(latency) AS max_latency,
            SUM(CASE WHEN status='offline' THEN 1 ELSE 0 END) AS offline_count,
            COUNT(*) AS total_checks
     FROM ping_logs
     WHERE ip_id = ? AND DATE(created_at) >= ? AND DATE(created_at) <= ?
     GROUP BY DATE(created_at)
     ORDER BY log_date DESC"
);
$stmt->execute([$report_id, $thirtyDaysAgo, $today]);
$dailyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate weekly aggregates within the month
$stmt = $conn->prepare(
    "SELECT 
        YEARWEEK(created_at) AS year_week,
        MIN(DATE(created_at)) AS week_start,
        MAX(DATE(created_at)) AS week_end,
        AVG(latency) AS avg_latency,
        SUM(CASE WHEN status='offline' THEN 1 ELSE 0 END) AS offline_count,
        COUNT(*) AS total_checks
     FROM ping_logs
     WHERE ip_id = ? AND DATE(created_at) >= ?
     GROUP BY YEARWEEK(created_at)
     ORDER BY year_week ASC"
);
$stmt->execute([$report_id, $thirtyDaysAgo]);
$weeklyAggregates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate overall monthly metrics
$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total_checks,
            SUM(CASE WHEN status='offline' THEN 1 ELSE 0 END) AS offline_count,
            AVG(latency) AS avg_latency,
            MIN(latency) AS min_latency,
            MAX(latency) AS max_latency
     FROM ping_logs
     WHERE ip_id = ? AND DATE(created_at) >= ?"
);
$stmt->execute([$report_id, $thirtyDaysAgo]);
$monthlyMetrics = $stmt->fetch(PDO::FETCH_ASSOC);

$monthlyUptime = $monthlyMetrics['total_checks'] > 0 
        ? round(($monthlyMetrics['total_checks'] - $monthlyMetrics['offline_count']) / $monthlyMetrics['total_checks'] * 100, 2)
        : 0;

// Initialize TCPDF
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('IT Network Monitoring');
$pdf->SetAuthor('IT Department');
$pdf->SetTitle('Monthly Network Device Status Report');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(TRUE, 10);
$pdf->AddPage();

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

<h1>Monthly Network Device Status Report</h1>
<p class="subtitle">Generated on ' . date('F j, Y') . ' for period ' . date('M j', strtotime($thirtyDaysAgo)) . ' - ' . date('M j, Y') . '</p>

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
        <th>Monitoring Since</th>
        <td>' . date("F j, Y", strtotime($device['date'])) . '</td>
    </tr>
</table>

<div class="section">
    <h2>Monthly Performance Summary</h2>
</div>';

// Monthly metrics box
$html .= '
<div class="metrics-box">
    <div class="metrics-title">MONTHLY UPTIME</div>
    <div class="metrics-value ' . ($monthlyUptime >= 99.9 ? 'status-good' : ($monthlyUptime >= 95 ? 'status-warning' : 'status-critical')) . '">
        ' . $monthlyUptime . '%
    </div>
    <div>Average Latency: ' . round($monthlyMetrics['avg_latency'], 2) . ' ms</div>
    <div>Min Latency: ' . round($monthlyMetrics['min_latency'], 2) . ' ms | Max Latency: ' . round($monthlyMetrics['max_latency'], 2) . ' ms</div>
    <div>Total Checks: ' . $monthlyMetrics['total_checks'] . ' | Failed Checks: ' . $monthlyMetrics['offline_count'] . '</div>
</div>';

// Weekly aggregates table
$html .= '
<div class="section">
    <h2>Weekly Performance Summary</h2>
</div>

<table class="stats">
    <tr>
        <th>Week Period</th>
        <th>Avg Latency</th>
        <th>Offline</th>
        <th>Checks</th>
        <th>Uptime %</th>
    </tr>';

foreach ($weeklyAggregates as $week) {
    $weekUptime = $week['total_checks'] > 0 
            ? round(($week['total_checks'] - $week['offline_count']) / $week['total_checks'] * 100, 2)
            : 0;
            
    $uptimeClass = $weekUptime >= 99.9 ? 'status-good' : ($weekUptime >= 95 ? 'status-warning' : 'status-critical');
    
    $html .= '
    <tr>
        <td>' . date('M j', strtotime($week['week_start'])) . ' - ' . date('M j', strtotime($week['week_end'])) . '</td>
        <td>' . number_format($week['avg_latency'], 2) . ' ms</td>
        <td>' . $week['offline_count'] . '</td>
        <td>' . $week['total_checks'] . '</td>
        <td class="' . $uptimeClass . '">' . $weekUptime . '%</td>
    </tr>';
}

$html .= '</table>';

// Daily stats table
$html .= '
<div class="section">
    <h2>Daily Performance Statistics</h2>
</div>

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
    $dayUptime = $day['total_checks'] > 0 
            ? round(($day['total_checks'] - $day['offline_count']) / $day['total_checks'] * 100, 2)
            : 0;
            
    $uptimeClass = $dayUptime >= 99.9 ? 'status-good' : ($dayUptime >= 95 ? 'status-warning' : 'status-critical');
    
    $html .= '
    <tr>
        <td>' . date('D, M j', strtotime($day['log_date'])) . '</td>
        <td>' . number_format($day['avg_latency'], 2) . ' ms</td>
        <td>' . number_format($day['min_latency'], 2) . ' ms</td>
        <td>' . number_format($day['max_latency'], 2) . ' ms</td>
        <td>' . $day['offline_count'] . '</td>
        <td>' . $day['total_checks'] . '</td>
        <td class="' . $uptimeClass . '">' . $dayUptime . '%</td>
    </tr>';
}

$html .= '</table>';

// Status summary
$html .= '
<div class="section">
    <h2>Monthly Status Summary</h2>
</div>';

if ($monthlyUptime < 95) {
    $html .= '
    <div style="border-left: 5px solid #F44336; padding: 10px; background-color: #FFEBEE;">
        <strong>Critical Issue:</strong> Device uptime is below acceptable threshold for the month. Recommend immediate investigation and service review.
    </div>';
} elseif ($monthlyUptime < 99) {
    $html .= '
    <div style="border-left: 5px solid #FF9800; padding: 10px; background-color: #FFF3E0;">
        <strong>Warning:</strong> Device experiencing intermittent connectivity issues this month. Schedule maintenance and review network configuration.
    </div>';
} else {
    $html .= '
    <div style="border-left: 5px solid #4CAF50; padding: 10px; background-color: #E8F5E9;">
        <strong>Good Status:</strong> Device operating within normal parameters this month.
    </div>';
}

// Add a section for outage incidents if any
if ($monthlyMetrics['offline_count'] > 0) {
    $html .= '
    <div class="section">
        <h2>Notable Incidents</h2>
    </div>';
    
    // Get most significant outages
    $stmt = $conn->prepare(
        "SELECT DATE(created_at) AS outage_date, 
                COUNT(*) AS consecutive_failures
         FROM ping_logs
         WHERE ip_id = ? AND status = 'offline' AND DATE(created_at) >= ?
         GROUP BY DATE(created_at)
         HAVING consecutive_failures >= 3
         ORDER BY consecutive_failures DESC
         LIMIT 5"
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
                <td>' . date('D, M j, Y', strtotime($outage['outage_date'])) . '</td>
                <td>' . $outage['consecutive_failures'] . '</td>
                <td class="' . $severityClass . '">' . $severity . '</td>
            </tr>';
        }
        
        $html .= '</table>';
    } else {
        $html .= '<p>No significant outages detected in the monitoring period.</p>';
    }
}

// Add recommendations based on monthly performance
$html .= '
<div class="section">
    <h2>Analysis & Recommendations</h2>
</div>';

if ($monthlyUptime < 95) {
    $html .= '
    <div style="padding: 10px;">
        <p><strong>Critical Issues Detected:</strong></p>
        <ul>
            <li>Device experiencing significant downtime over the past month</li>
            <li>Peak latency of ' . round($monthlyMetrics['max_latency'], 2) . ' ms indicates potential network congestion</li>
            <li>Total of ' . $monthlyMetrics['offline_count'] . ' failed checks requires immediate attention</li>
        </ul>
        <p><strong>Recommendations:</strong></p>
        <ul>
            <li>Schedule immediate maintenance</li>
            <li>Check physical network connections</li>
            <li>Review network configuration</li>
            <li>Consider hardware inspection or replacement</li>
        </ul>
    </div>';
} elseif ($monthlyUptime < 99) {
    $html .= '
    <div style="padding: 10px;">
        <p><strong>Issues Detected:</strong></p>
        <ul>
            <li>Device experiencing intermittent connectivity issues</li>
            <li>Average latency of ' . round($monthlyMetrics['avg_latency'], 2) . ' ms indicates potential network issues</li>
        </ul>
        <p><strong>Recommendations:</strong></p>
        <ul>
            <li>Schedule proactive maintenance</li>
            <li>Monitor device closely over next reporting period</li>
            <li>Check for potential network congestion during peak hours</li>
        </ul>
    </div>';
} else {
    $html .= '
    <div style="padding: 10px;">
        <p><strong>Status:</strong> Device operating normally</p>
        <p><strong>Recommendations:</strong></p>
        <ul>
            <li>Continue standard monitoring</li>
            <li>No immediate action required</li>
            <li>Consider scheduled preventative maintenance to maintain optimal performance</li>
        </ul>
    </div>';
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
$pdf->Output('monthly_network_report_' . $report_id . '.pdf', 'I');
?>