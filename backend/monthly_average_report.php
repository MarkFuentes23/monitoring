<?php
require_once '../config/db.php';
requireLogin();

function getMonthlyAverageData($report_id, $selectedMonth, $selectedYear) {
    global $conn;

    // Define business hours window
    $businessStart       = '08:00:00';
    $businessEnd         = '18:00:00';
    // Calculate business hours duration and expected intervals per day
    $businessHoursPerDay = (strtotime($businessEnd) - strtotime($businessStart)) / 3600; // 10 hours
    $intervalsPerDay     = $businessHoursPerDay * 4; // 4 pings per hour (40 pings per day)

    // 1) Fetch daily latency stats within business hours
    $stmt = $conn->prepare(
        "SELECT
            DATE_FORMAT(created_at, '%Y-%m-%d') AS log_date,
            AVG(latency) AS avg_latency,
            MIN(latency) AS min_latency,
            MAX(latency) AS max_latency
         FROM ping_logs
         WHERE ip_id = ?
           AND YEAR(created_at) = ?
           AND MONTH(created_at) = ?
           AND TIME(created_at) BETWEEN ? AND ?
         GROUP BY log_date
         ORDER BY log_date"
    );
    $stmt->execute([$report_id, $selectedYear, $selectedMonth, $businessStart, $businessEnd]);
    $monthly_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Initialize default totals for days with no pings
    foreach ($monthly_stats as &$day) {
        $day['total_checks'] = $intervalsPerDay;
        $day['offline_count'] = 0;
        $day['uptime_percent'] = 100;
    }
    unset($day);

    // 2) Fetch all pings for offline detection
    $stmt2 = $conn->prepare("
        SELECT 
            created_at,
            status
        FROM ping_logs
        WHERE ip_id = ? AND YEAR(created_at) = ? AND MONTH(created_at) = ?
        ORDER BY created_at
    ");
    $stmt2->execute([$report_id, $selectedYear, $selectedMonth]);
    $all_pings = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    // 3) Process all pings to detect continuous offline periods
    $offline_periods = [];
    $continuous_offline = 0;
    $is_offline = false;

    foreach ($all_pings as $ping) {
        $current_date = date('Y-m-d', strtotime($ping['created_at']));
        
        // Initialize date in offline_periods array if not exists
        if (!isset($offline_periods[$current_date])) {
            $offline_periods[$current_date] = 0;
        }
        
        if ($ping['status'] == 'offline') {
            $continuous_offline++;
            
            // If three consecutive checks are offline, consider it a true offline period
            if ($continuous_offline >= 3) {
                if (!$is_offline) {
                    $is_offline = true;
                    $offline_periods[$current_date]++;
                }
            }
        } else {
            // Reset counter if ping is online
            $continuous_offline = 0;
            $is_offline = false;
        }
    }

    // 4) Add the offline_periods data to monthly_stats and calculate uptime
    foreach ($monthly_stats as &$day) {
        $date = $day['log_date'];
        $day['offline_count'] = isset($offline_periods[$date]) ? $offline_periods[$date] : 0;
        
        // Calculate uptime percentage - FIXED: total offline divided by 40 times 100
        $day['uptime_percent'] = 100 - (($day['offline_count'] / $intervalsPerDay) * 100);
    }
    unset($day);

    // 5) Compute monthly aggregates
    $total_days = count($monthly_stats);
    $total_offline_periods = array_sum(array_values($offline_periods));
    $sum_latency = array_sum(array_column($monthly_stats, 'avg_latency'));
    $min_vals = array_column($monthly_stats, 'min_latency');
    $max_vals = array_column($monthly_stats, 'max_latency');

    $monthly_total = [
        'avg_latency' => $total_days ? ($sum_latency / $total_days) : 0,
        'min_latency' => $min_vals ? min($min_vals) : 0,
        'max_latency' => $max_vals ? max($max_vals) : 0,
        'total_checks' => $intervalsPerDay * $total_days,
        'total_offline_count' => $total_offline_periods,
        // FIXED: monthly uptime% = 100 - (total_offline_periods / (total_days * intervalsPerDay) * 100)
        'avg_uptime_percent' => 100 - (($total_offline_periods / ($total_days * $intervalsPerDay)) * 100),
    ];

    // Calculate overall metrics for the device overview
    $downtime_hours = $total_offline_periods;
    $uptime_percent = $monthly_total['avg_uptime_percent'];

    return [
        'monthly_stats' => $monthly_stats,
        'monthly_total' => $monthly_total,
        'downtime_hours' => $downtime_hours,
        'uptime_percent' => $uptime_percent,
        'total_offline_periods' => $total_offline_periods,
        'avg_latency' => $monthly_total['avg_latency'],
        'days_running' => $total_days
    ];
}