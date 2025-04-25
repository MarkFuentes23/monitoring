<?php
// Include TCPDF library
require_once '../vendor/autoload.php';
require_once('../config/db.php');

// Validate report ID
if (!isset($_GET['report']) || !is_numeric($_GET['report'])) {
    $_SESSION['error'] = "Invalid report ID.";
    header("Location: monitoring.php");
    exit;
}
$report_id = (int)$_GET['report'];

// Month filter
$selectedMonth = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$selectedYear = date('Y');
$monthName = date('F', mktime(0,0,0,$selectedMonth,1));

// Fetch device info
$stmt = $conn->prepare("SELECT * FROM add_ip WHERE id = ?");
$stmt->execute([$report_id]);
$device_data = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$device_data) {
    $_SESSION['error'] = "Device not found!";
    header("Location: monitoring.php");
    exit;
}

// Fetch monthly logs
$stmt = $conn->prepare("
    SELECT 
      p.created_at, 
      p.latency, 
      p.status,
      a.ip_address,
      a.location,
      a.category,
      a.description
    FROM ping_logs p
    JOIN add_ip a ON p.ip_id = a.id
    WHERE p.ip_id = ?
      AND YEAR(p.created_at) = ?
      AND MONTH(p.created_at) = ?
      AND HOUR(p.created_at) >= 8
      AND HOUR(p.created_at) < 17
      AND WEEKDAY(p.created_at) < 5
    ORDER BY p.created_at
");
$stmt->execute([$report_id, $selectedYear, $selectedMonth]);
$monthly_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get daily statistics for selected month
$stmt = $conn->prepare("
    SELECT 
        DATE(created_at) AS log_date,
        AVG(latency) AS avg_latency,
        MIN(latency) AS min_latency,
        MAX(latency) AS max_latency,
        SUM(CASE WHEN status = 'offline' THEN 1 ELSE 0 END) AS offline_count,
        SUM(CASE WHEN status = 'online' THEN 1 ELSE 0 END) AS online_count,
        COUNT(*) AS total_checks
    FROM ping_logs
    WHERE ip_id = ? 
      AND YEAR(created_at) = ?
      AND MONTH(created_at) = ?
      AND HOUR(created_at) >= 8
      AND HOUR(created_at) < 17
      AND WEEKDAY(created_at) < 5
    GROUP BY DATE(created_at)
    ORDER BY log_date
");
$stmt->execute([$report_id, $selectedYear, $selectedMonth]);
$daily_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate monthly summary stats
$total_checks = 0;
$total_offline = 0;
$total_online = 0;
$sum_latency = 0;
$max_latency = 0;
$min_latency = PHP_INT_MAX;

foreach ($daily_stats as $day) {
    $total_checks += $day['total_checks'];
    $total_offline += $day['offline_count'];
    $total_online += $day['online_count'];
    $sum_latency += ($day['avg_latency'] * $day['total_checks']);
    $max_latency = max($max_latency, $day['max_latency']);
    $min_latency = min($min_latency, $day['min_latency']);
}

$avg_latency = $total_checks > 0 ? $sum_latency / $total_checks : 0;
$online_percentage = $total_checks > 0 ? ($total_online / $total_checks) * 100 : 0;
$offline_percentage = $total_checks > 0 ? ($total_offline / $total_checks) * 100 : 0;
$uptime_percentage = $total_checks > 0 ? (($total_checks - $total_offline) / $total_checks) * 100 : 0;

// Create new PDF document
class MYPDF extends TCPDF {
    public function Header() {
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 10, 'Network Device Monthly Performance Report', 0, 1, 'C');
        $this->SetFont('helvetica', '', 9);
        $this->Cell(0, 5, 'Generated: ' . date('F j, Y, g:i a'), 0, 1, 'R');
        $this->Line(10, $this->GetY(), $this->getPageWidth() - 10, $this->GetY());
        $this->Ln(5);
    }
    
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C');
    }
}

// Initialize PDF
$pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Network Monitoring System');
$pdf->SetAuthor('System Administrator');
$pdf->SetTitle('Monthly Report - ' . $device_data['description']);
$pdf->SetSubject('Network Device Performance Report');

// Set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

// Set margins
$pdf->SetMargins(10, 30, 10);
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(10);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 15);

// Add a page
$pdf->AddPage();

// Device information section
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, $device_data['description'] . ' - ' . $monthName . ' ' . $selectedYear . ' Report', 0, 1);

$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 7, 'Device Information', 0, 1, 'L');

$pdf->SetFont('helvetica', '', 10);
$pdf->SetFillColor(240, 240, 240);

// Create device info table
$device_info = array(
    array('IP Address:', $device_data['ip_address']),
    array('Description:', $device_data['description']),
    array('Location:', $device_data['location']),
    array('Category:', $device_data['category']),
    array('Current Status:', $device_data['status'] === 'online' ? 'Online' : 'Offline'),
    array('Current Latency:', $device_data['latency'] . ' ms')
);

foreach($device_info as $i => $row) {
    $fill = ($i % 2 == 0) ? true : false;
    $pdf->Cell(40, 7, $row[0], 1, 0, 'L', $fill);
    $pdf->Cell(140, 7, $row[1], 1, 1, 'L', $fill);
}

$pdf->Ln(5);

// Monthly Performance Summary
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 7, 'Monthly Performance Summary', 0, 1, 'L');

$pdf->SetFont('helvetica', '', 10);

// Create summary table with online/offline totals and percentages
$summary_data = array(
    array('Total Checks:', $total_checks),
    array('Online Count:', $total_online),
    array('Offline Count:', $total_offline),
    array('Online Percentage:', number_format($online_percentage, 2) . '%'),
    array('Offline Percentage:', number_format($offline_percentage, 2) . '%'),
    array('Uptime Percentage:', number_format($uptime_percentage, 2) . '%'),
    array('Average Latency:', number_format($avg_latency, 2) . ' ms'),
    array('Minimum Latency:', number_format($min_latency, 2) . ' ms'),
    array('Maximum Latency:', number_format($max_latency, 2) . ' ms')
);

// Create summary table
foreach($summary_data as $i => $row) {
    $fill = ($i % 2 == 0) ? true : false;
    $pdf->Cell(60, 7, $row[0], 1, 0, 'L', $fill);
    $pdf->Cell(120, 7, $row[1], 1, 1, 'L', $fill);
}

$pdf->Ln(5);

// Status Rating
$statusRating = '';
if ($uptime_percentage == 100) {
    $statusRating = 'Excellent';
} elseif ($uptime_percentage >= 99.5) {
    $statusRating = 'Very Good';
} elseif ($uptime_percentage >= 95) {
    $statusRating = 'Average';
} else {
    $statusRating = 'Poor';
}

$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(40, 10, 'Overall Status Rating:', 0, 0);
$pdf->SetFont('helvetica', 'B', 11);

// Set color based on rating
switch ($statusRating) {
    case 'Excellent':
    case 'Very Good':
        $pdf->SetTextColor(0, 128, 0); // Green
        break;
    case 'Average':
        $pdf->SetTextColor(255, 165, 0); // Orange
        break;
    case 'Poor':
        $pdf->SetTextColor(255, 0, 0); // Red
        break;
}

$pdf->Cell(0, 10, $statusRating, 0, 1);
$pdf->SetTextColor(0, 0, 0); // Reset to black

$pdf->Ln(5);

// Daily Performance Table
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 7, 'Daily Performance (Working Hours: Mon-Fri, 8AM-5PM)', 0, 1, 'L');
$pdf->SetFont('helvetica', 'B', 9);

// Table header
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell(25, 7, 'Date', 1, 0, 'C', true);
$pdf->Cell(25, 7, 'Total Checks', 1, 0, 'C', true);
$pdf->Cell(25, 7, 'Online', 1, 0, 'C', true);
$pdf->Cell(25, 7, 'Offline', 1, 0, 'C', true);
$pdf->Cell(30, 7, 'Online %', 1, 0, 'C', true);
$pdf->Cell(30, 7, 'Avg Latency (ms)', 1, 0, 'C', true);
$pdf->Cell(30, 7, 'Status', 1, 1, 'C', true);

$pdf->SetFont('helvetica', '', 9);
$pdf->SetFillColor(245, 245, 245);

// Table data
if (!empty($daily_stats)) {
    foreach ($daily_stats as $i => $day) {
        $fill = ($i % 2 == 0) ? true : false;
        $date = date("M j, Y", strtotime($day['log_date']));
        $online_pct = $day['total_checks'] > 0 ? ($day['online_count'] / $day['total_checks']) * 100 : 0;
        
        // Determine status
        $status = '';
        if ($online_pct == 100) {
            $status = 'Excellent';
        } elseif ($online_pct >= 99.5) {
            $status = 'Very Good';
        } elseif ($online_pct >= 95) {
            $status = 'Average';
        } else {
            $status = 'Poor';
        }
        
        $pdf->Cell(25, 6, $date, 1, 0, 'C', $fill);
        $pdf->Cell(25, 6, $day['total_checks'], 1, 0, 'C', $fill);
        $pdf->Cell(25, 6, $day['online_count'], 1, 0, 'C', $fill);
        $pdf->Cell(25, 6, $day['offline_count'], 1, 0, 'C', $fill);
        $pdf->Cell(30, 6, number_format($online_pct, 2) . '%', 1, 0, 'C', $fill);
        $pdf->Cell(30, 6, number_format($day['avg_latency'], 2), 1, 0, 'C', $fill);
        $pdf->Cell(30, 6, $status, 1, 1, 'C', $fill);
    }
} else {
    $pdf->Cell(0, 10, 'No data available for this month', 1, 1, 'C');
}

$pdf->Ln(5);

// Add a page for detailed logs
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 7, 'Detailed Ping Logs - ' . $monthName . ' ' . $selectedYear, 0, 1, 'L');
$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell(0, 5, 'Records filtered for working hours only (Monday-Friday, 8AM-5PM)', 0, 'L');
$pdf->Ln(2);

// Table header for detailed logs
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell(30, 7, 'Date', 1, 0, 'C', true);
$pdf->Cell(20, 7, 'Time', 1, 0, 'C', true);
$pdf->Cell(20, 7, 'Status', 1, 0, 'C', true);
$pdf->Cell(20, 7, 'Latency', 1, 0, 'C', true);
$pdf->Cell(90, 7, 'Description', 1, 1, 'C', true);

$pdf->SetFont('helvetica', '', 8);
$pdf->SetFillColor(245, 245, 245);

// Display detailed logs
if (!empty($monthly_logs)) {
    foreach ($monthly_logs as $i => $log) {
        // If we're approaching the bottom of the page, add a new page
        if ($pdf->GetY() > 250) {
            $pdf->AddPage();
            
            // Reprint the header
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(0, 7, 'Detailed Ping Logs (Continued)', 0, 1, 'L');
            $pdf->Ln(2);
            
            // Table header for detailed logs
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetFillColor(220, 220, 220);
            $pdf->Cell(30, 7, 'Date', 1, 0, 'C', true);
            $pdf->Cell(20, 7, 'Time', 1, 0, 'C', true);
            $pdf->Cell(20, 7, 'Status', 1, 0, 'C', true);
            $pdf->Cell(20, 7, 'Latency', 1, 0, 'C', true);
            $pdf->Cell(90, 7, 'Description', 1, 1, 'C', true);
            
            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetFillColor(245, 245, 245);
        }
        
        $fill = ($i % 2 == 0) ? true : false;
        $date = date("M j, Y", strtotime($log['created_at']));
        $time = date("h:i A", strtotime($log['created_at']));
        
        $pdf->Cell(30, 6, $date, 1, 0, 'C', $fill);
        $pdf->Cell(20, 6, $time, 1, 0, 'C', $fill);
        $pdf->Cell(20, 6, ucfirst($log['status']), 1, 0, 'C', $fill);
        $pdf->Cell(20, 6, $log['latency'] . ' ms', 1, 0, 'C', $fill);
        $pdf->Cell(90, 6, $log['description'] . ' (' . $log['ip_address'] . ')', 1, 1, 'L', $fill);
    }
} else {
    $pdf->Cell(0, 10, 'No detailed logs available for this month', 1, 1, 'C');
}

// Close and output PDF document
$pdf->Output('device_' . $report_id . '_' . $monthName . '_report.pdf', 'I');

