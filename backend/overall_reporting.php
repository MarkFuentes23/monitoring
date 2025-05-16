<?php
/**
 * Calculate daily statistics for all IPs or filtered IPs
 *
 * @param array $filters Filter parameters
 * @return array Daily statistics
 */
function getGlobalDailyStats($filters = []) {
    global $conn;
    
    // Extract filter values
    $selectedMonth = $filters['month'] ?? date('n');
    $selectedYear  = $filters['year']  ?? date('Y');
    $location      = $filters['location'] ?? '';
    $category      = $filters['category'] ?? '';
    $ip_id         = $filters['ip_id']    ?? '';
    
    // Detect custom mode and compute start/end days, times, weekdays
    $isCustom    = (isset($filters['view_mode']) && $filters['view_mode'] === 'custom');
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $selectedMonth, $selectedYear);
    $startDay    = $isCustom ? (int)$filters['start_date'] : 1;
    $endDay      = $isCustom ? (int)$filters['end_date']   : $daysInMonth;
    $startTimeF  = $isCustom ? $filters['start_time']     : '00:00';
    $endTimeF    = $isCustom ? $filters['end_time']       : '23:59';
    $weekdays    = is_array($filters['weekdays']) ? $filters['weekdays'] : [$filters['weekdays']];

    // Build base filters for IP list
    $whereClause = "WHERE YEAR(p.created_at) = ? AND MONTH(p.created_at) = ?";
    $params      = [$selectedYear, $selectedMonth];

    if (!empty($ip_id)) {
        $whereClause .= " AND p.ip_id = ?";
        $params[]    = $ip_id;
    }
    if (!empty($location)) {
        $whereClause .= " AND a.location = ?";
        $params[]    = $location;
    }
    if (!empty($category)) {
        $whereClause .= " AND a.category = ?";
        $params[]    = $category;
    }

    // Business window & intervals
    $intervalsPerDay = 40; // 15-min intervals

    // Fetch unique IPs + last ping timestamp
    $ipsStmt = $conn->prepare(
        "SELECT 
            a.id,
            a.ip_address,
            a.description,
            a.location,
            a.category,
            MAX(p.created_at) AS last_ping
         FROM add_ip a
         JOIN ping_logs p ON p.ip_id = a.id
         $whereClause
         GROUP BY a.id, a.ip_address, a.description, a.location, a.category"
    );
    $ipsStmt->execute($params);
    $ips = $ipsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Initialize containers
    $daily_stats      = [];
    $ip_monthly_stats = [];

    foreach ($ips as $ip) {
        $key = $ip['id'];
        $ip_monthly_stats[$key] = [
            'id'               => $key,
            'ip_address'       => $ip['ip_address'],
            'description'      => $ip['description'],
            'location'         => $ip['location'],
            'category'         => $ip['category'],
            'last_ping'        => $ip['last_ping'],
            'total_offline'    => 0,
            'total_checks'     => 0,
            'total_latency'    => 0,
            'days_with_data'   => 0,
            'sum_daily_uptime' => 0,
        ];

        for ($day = $startDay; $day <= $endDay; $day++) {
            $date     = sprintf('%04d-%02d-%02d', $selectedYear, $selectedMonth, $day);
            $date_key = date('Y-m-d', strtotime($date));

            // Skip by weekday if custom
            if ($isCustom) {
                $dow = date('w', strtotime($date)); // 0=Sun...6=Sat
                if (!in_array((string)$dow, $weekdays, true)) {
                    continue;
                }
            }

            // Init day slot
            if (!isset($daily_stats[$date_key])) {
                $daily_stats[$date_key] = [
                    'log_date'     => $date_key,
                    'device_count' => 0,
                    'devices'      => []
                ];
            }

            // Daily checks in time window
            $dayStmt = $conn->prepare(
                "SELECT
                    COUNT(*) AS total_checks,
                    AVG(latency) AS avg_latency
                 FROM ping_logs
                 WHERE ip_id = ?
                   AND DATE(created_at) = ?
                   AND TIME(created_at) BETWEEN ? AND ?"
            );
            $dayStmt->execute([$key, $date, $startTimeF, $endTimeF]);
            $dayData = $dayStmt->fetch(PDO::FETCH_ASSOC);
            if (empty($dayData['total_checks'])) {
                continue;
            }

            // Fetch timestamps & statuses
            $timeStmt = $conn->prepare(
                "SELECT TIME(created_at) AS chk, status
                 FROM ping_logs
                 WHERE ip_id = ?
                   AND DATE(created_at) = ?
                   AND TIME(created_at) BETWEEN ? AND ?
                 ORDER BY created_at"
            );
            $timeStmt->execute([$key, $date, $startTimeF, $endTimeF]);
            $timesData = $timeStmt->fetchAll(PDO::FETCH_ASSOC);

            // Count offline spans and duration
            $offlineCount       = 0;
            $offlineDurationSec = 0;
            $inOffline          = false;
            $spanStartTs        = null;
            $countedThisSpan    = false;
            $prevTs             = null;

            foreach ($timesData as $tp) {
                $ts   = strtotime($tp['chk']);
                $stat = $tp['status'];

                if ($stat === 'offline') {
                    if (!$inOffline) {
                        // start new offline span
                        $inOffline       = true;
                        $spanStartTs     = $ts;
                        $countedThisSpan = false;
                    }
                    // accumulate duration
                    if ($prevTs !== null) {
                        $offlineDurationSec += ($ts - $prevTs);
                    }
                } else {
                    if ($inOffline) {
                        // closing span on first online: duration
                        $duration = $ts - $spanStartTs;
                        if ($duration >= 900 && !$countedThisSpan) {
                            $offlineCount++;
                        }
                        $offlineDurationSec += ($ts - $prevTs);
                    }
                    $inOffline = false;
                }
                $prevTs = $ts;
            }
            // if still offline at end of day window
            if ($inOffline) {
                $endOfDayTs = strtotime("$date $endTimeF");
                $offlineDurationSec += ($endOfDayTs - max($spanStartTs, $prevTs));
                // count one span at day-end if ≥15m and not yet counted
                if (!$countedThisSpan && ($endOfDayTs - $spanStartTs) >= 900) {
                    $offlineCount++;
                }
            }

            $uptime_percent = 100 - (($offlineCount / $intervalsPerDay) * 100);

            // Store per-day
            $daily_stats[$date_key]['device_count']++;
            $daily_stats[$date_key]['devices'][$key] = [
                'id'             => $key,
                'ip_address'     => $ip['ip_address'],
                'description'    => $ip['description'],
                'location'       => $ip['location'],
                'category'       => $ip['category'],
                'avg_latency'    => $dayData['avg_latency'],
                'offline_count'  => $offlineCount,
                'offline_hours'  => round($offlineDurationSec / 3600, 2),
                'total_checks'   => $dayData['total_checks'],
                'uptime_percent' => $uptime_percent
            ];

            // Daily status text/class
            if      ($uptime_percent == 100) {
                $cls = 'bg-success text-white'; $txt = 'Excellent';
            } elseif ($uptime_percent >= 99.5) {
                $cls = 'bg-success text-white'; $txt = 'Very Good';
            } elseif ($uptime_percent >= 95) {
                $cls = 'bg-warning';             $txt = 'Average';
            } else {
                $cls = 'bg-danger text-white';   $txt = 'Poor';
            }
            $daily_stats[$date_key]['devices'][$key]['status_class'] = $cls;
            $daily_stats[$date_key]['devices'][$key]['status_text']  = $txt;

            // Accumulate monthly
            $ip_monthly_stats[$key]['total_offline']    += $offlineCount;
            $ip_monthly_stats[$key]['total_checks']     += $dayData['total_checks'];
            $ip_monthly_stats[$key]['total_latency']    += $dayData['avg_latency'];
            $ip_monthly_stats[$key]['days_with_data']   ++;
            $ip_monthly_stats[$key]['sum_daily_uptime'] += $uptime_percent;
        }
    }

    // Finalize monthly stats
    foreach ($ip_monthly_stats as $k => &$m) {
        if ($m['days_with_data'] > 0) {
            $m['monthly_avg_latency']    = $m['total_latency'] / $m['days_with_data'];
            $m['monthly_uptime_percent'] = $m['sum_daily_uptime'] / $m['days_with_data'];
            $u = $m['monthly_uptime_percent'];
            if      ($u == 100) {
                $m['monthly_status_class'] = 'bg-success text-white'; $m['monthly_status_text']  = 'Excellent';
            } elseif ($u >= 99.5) {
                $m['monthly_status_class'] = 'bg-success text-white'; $m['monthly_status_text']  = 'Very Good';
            } elseif ($u >= 95) {
                $m['monthly_status_class'] = 'bg-warning';            $m['monthly_status_text']  = 'Average';
            } else {
                $m['monthly_status_class'] = 'bg-danger text-white';  $m['monthly_status_text']  = 'Poor';
            }
        } else {
            $m['monthly_avg_latency']    = 0;
            $m['monthly_uptime_percent'] = 0;
            $m['monthly_status_class']   = 'bg-secondary text-white';
            $m['monthly_status_text']    = 'No Data';
        }
    }
    unset($m);

    // Sort daily stats
    $daily_array = array_values($daily_stats);
    usort($daily_array, fn($a, $b) => strcmp($a['log_date'], $b['log_date']));

    return [
        'daily_stats'      => $daily_array,
        'ip_monthly_stats' => array_values($ip_monthly_stats),
        'monthly_total'    => calculateOverallMonthlyStats(array_values($ip_monthly_stats)),
        'total_days'       => count($daily_array)
    ];
}

/**
 * Calculate overall monthly statistics from IP-specific stats
 *
 * @param array $ip_stats Array of IP monthly statistics
 * @return array Overall monthly statistics
 */
function calculateOverallMonthlyStats(array $ip_stats): array {
    $overall = [
        'total_devices'       => count($ip_stats),
        'avg_latency'         => 0,
        'total_offline_count' => 0,
        'total_checks'        => 0,
        'avg_uptime_percent'  => 0,
    ];

    if ($overall['total_devices'] > 0) {
        $latency_sum = $offline_sum = $checks_sum = 0;
        foreach ($ip_stats as $ip) {
            $latency_sum += $ip['monthly_avg_latency'] ?? 0;
            $offline_sum += $ip['total_offline']        ?? 0;
            $checks_sum  += $ip['total_checks']         ?? 0;
        }

        $overall['avg_latency']         = $latency_sum / $overall['total_devices'];
        $overall['total_offline_count'] = $offline_sum;
        $overall['total_checks']        = $checks_sum;
        $overall['avg_uptime_percent']  = $checks_sum > 0
            ? 100 - (($offline_sum / $checks_sum) * 100)
            : 0;

        $u = $overall['avg_uptime_percent'];
        if      ($u == 100) {
            $overall['status_text']  = 'Excellent';
            $overall['status_class'] = 'bg-success text-white';
        } elseif ($u >= 99.5) {
            $overall['status_text']  = 'Very Good';
            $overall['status_class'] = 'bg-success text-white';
        } elseif ($u >= 95) {
            $overall['status_text']  = 'Average';
            $overall['status_class'] = 'bg-warning';
        } else {
            $overall['status_text']  = 'Poor';
            $overall['status_class'] = 'bg-danger text-white';
        }
    }

    return $overall;
}
