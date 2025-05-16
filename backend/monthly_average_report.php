<?php
function getMonthlyAverageData($report_id, $selectedMonth, $selectedYear) {
    global $conn;

    // ─── 0) Business-hour constants ───────────────────────────────────────────
    $businessStart       = '08:00:00';
    $businessEnd         = '18:00:00';
    $businessHoursPerDay = (strtotime($businessEnd) - strtotime($businessStart)) / 3600; // 10h
    $intervalsPerDay     = $businessHoursPerDay * 4; // 40 pings/day

    // Month start/end for remarks lookup
    $monthStart = "$selectedYear-$selectedMonth-01 00:00:00";
    $monthEnd   = date('Y-m-t 23:59:59', strtotime("$selectedYear-$selectedMonth-01"));

    // ─── 1) Fetch daily latency stats within business hours ────────────────────
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
    $stmt->execute([
        $report_id,
        $selectedYear,
        $selectedMonth,
        $businessStart,
        $businessEnd
    ]);
    $monthly_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Initialize defaults for days with no pings
    foreach ($monthly_stats as &$day) {
        $day['total_checks']   = $intervalsPerDay;
        $day['offline_count']  = 0;
        $day['adjusted_offline_count'] = 0; // New field for offline count after exclusions
        $day['uptime_percent'] = 100;
    }
    unset($day);

    // ─── 2) Detect offline periods within business hours ───────────────────────
    $stmt2 = $conn->prepare("
        SELECT created_at, status
        FROM ping_logs
        WHERE ip_id = ?
          AND YEAR(created_at) = ?
          AND MONTH(created_at) = ?
          AND TIME(created_at) BETWEEN ? AND ?
        ORDER BY created_at
    ");
    $stmt2->execute([
        $report_id,
        $selectedYear,
        $selectedMonth,
        $businessStart,
        $businessEnd
    ]);
    $all_pings = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    $offline_periods    = [];
    $continuous_offline = 0;
    $is_offline         = false;
    $previous_date      = '';

    foreach ($all_pings as $ping) {
        $current_date = date('Y-m-d', strtotime($ping['created_at']));

        if ($previous_date !== '' && $previous_date !== $current_date) {
            $continuous_offline = 0;
            $is_offline         = false;
        }
        if (!isset($offline_periods[$current_date])) {
            $offline_periods[$current_date] = 0;
        }

        if ($ping['status'] === 'offline') {
            $continuous_offline++;
            if ($continuous_offline >= 3 && !$is_offline) {
                $is_offline = true;
                $offline_periods[$current_date]++;
            }
        } else {
            $continuous_offline = 0;
            $is_offline         = false;
        }

        $previous_date = $current_date;
    }

    // ─── 3) Get exclusions for this month ─────────────────────────────────────
    $stmtExcl = $conn->prepare("
        SELECT exclusion_date, COUNT(*) as exclusion_count
        FROM offline_exclusions
        WHERE ip_id = ?
          AND exclusion_date BETWEEN ? AND ?
        GROUP BY exclusion_date
    ");
    $stmtExcl->execute([
        $report_id,
        date('Y-m-01', strtotime("$selectedYear-$selectedMonth-01")),
        date('Y-m-t', strtotime("$selectedYear-$selectedMonth-01"))
    ]);
    $exclusions = $stmtExcl->fetchAll(PDO::FETCH_KEY_PAIR);

    // ─── 4) Load all offline_remarks overlapping this month (both excluded and non-excluded) ──
    $stmtR = $conn->prepare("
        SELECT id, date_from, date_to, remarks, is_excluded, excluded_at
        FROM offline_remarks
        WHERE ip_id = ?
          AND (
                (date_from BETWEEN ? AND ?)
             OR (date_to   BETWEEN ? AND ?)
             OR (date_from <= ? AND date_to >= ?)
          )
        ORDER BY date_from
    ");
    $stmtR->execute([
        $report_id,
        $monthStart, $monthEnd,
        $monthStart, $monthEnd,
        $monthStart, $monthEnd
    ]);
    $rawRemarks = $stmtR->fetchAll(PDO::FETCH_ASSOC);

    $remarkRanges = [];
    foreach ($rawRemarks as $r) {
        $remarkRanges[] = [
            'id'          => $r['id'],
            'date_from'   => $r['date_from'],
            'date_to'     => $r['date_to'],
            'remark'      => $r['remarks'],
            'is_excluded' => (bool)$r['is_excluded'],
            'excluded_at' => $r['excluded_at']
        ];
    }

    // ─── 5) Count excluded events per day for offline adjustments ──────────────
    $excludedCountsByDate = [];
    foreach ($remarkRanges as $rr) {
        if ($rr['is_excluded']) {
            $date = date('Y-m-d', strtotime($rr['date_from']));
            if (!isset($excludedCountsByDate[$date])) {
                $excludedCountsByDate[$date] = 0;
            }
            $excludedCountsByDate[$date]++;
        }
    }

    // ─── 6) Annotate each day: offline + uptime + all remarks ─────────────────
    foreach ($monthly_stats as &$day) {
        $date     = $day['log_date'];
        $dayStart = strtotime("$date $businessStart");
        $dayEnd   = strtotime("$date $businessEnd");

        // Set raw offline count (before exclusions)
        $day['offline_count'] = $offline_periods[$date] ?? 0;
        
        // Calculate adjusted offline count (after exclusions)
        $excluded_count = $excludedCountsByDate[$date] ?? 0;
        $day['adjusted_offline_count'] = max(0, $day['offline_count'] - $excluded_count);
        
        // Calculate uptime based on adjusted offline count
        $day['uptime_percent'] = 100 - (($day['adjusted_offline_count'] / $intervalsPerDay) * 100);

        // Add all remarks, both active and excluded
        $day['remarks'] = [];
        foreach ($remarkRanges as $rr) {
            $fromTs = strtotime($rr['date_from']);
            $toTs   = strtotime($rr['date_to']);
            if (!($toTs < $dayStart || $fromTs > $dayEnd)) {
                $day['remarks'][] = [
                    'id'          => $rr['id'],
                    'from'        => date('H:i', $fromTs),
                    'to'          => date('H:i', $toTs),
                    'remark'      => $rr['remark'],
                    'is_excluded' => $rr['is_excluded'],
                    'excluded_at' => $rr['excluded_at'] ? date('Y-m-d H:i:s', strtotime($rr['excluded_at'])) : null
                ];
            }
        }
    }
    unset($day);

    // ─── 7) Compute monthly totals ─────────────────────────────────────────────
    $total_days                = count($monthly_stats);
    $total_offline_periods     = array_sum(array_column($monthly_stats, 'offline_count'));
    $adjusted_offline_periods  = array_sum(array_column($monthly_stats, 'adjusted_offline_count'));
    $sum_latency               = array_sum(array_column($monthly_stats, 'avg_latency'));
    $min_vals                  = array_column($monthly_stats, 'min_latency');
    $max_vals                  = array_column($monthly_stats, 'max_latency');

    $monthly_total = [
        'avg_latency'            => $total_days ? ($sum_latency / $total_days) : 0,
        'min_latency'            => $min_vals ? min($min_vals) : 0,
        'max_latency'            => $max_vals ? max($max_vals) : 0,
        'total_checks'           => $intervalsPerDay * $total_days,
        'total_offline_count'    => $total_offline_periods,
        'adjusted_offline_count' => $adjusted_offline_periods,
        'avg_uptime_percent'     => 100 - (($adjusted_offline_periods / ($total_days * $intervalsPerDay)) * 100),
        'remarks'                => []  // leave as empty array for consistency
    ];

    $downtime_minutes = $adjusted_offline_periods * 45;
    $uptime_percent   = $monthly_total['avg_uptime_percent'];

    return [
        'monthly_stats'         => $monthly_stats,
        'monthly_total'         => $monthly_total,
        'downtime_minutes'      => $downtime_minutes,
        'uptime_percent'        => $uptime_percent,
        'total_offline_periods' => $adjusted_offline_periods,
        'avg_latency'           => $monthly_total['avg_latency'],
        'days_running'          => $total_days
    ];
}