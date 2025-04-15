<?php
// ping_functions.php

/**
 * Nagpapadala ng ping sa mga IP address nang sabay-sabay gamit ang proc_open.
 * @param array $ips Array ng mga IP address na ipi-ping
 * @return array Resulta ng bawat IP kasama ang latency at status
 */
function parallelPing(array $ips)
{
    $processes = [];
    foreach ($ips as $id => $ip) {
        $cmd = "ping -n 4 " . escapeshellarg($ip);
        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];
        $proc = proc_open($cmd, $descriptorspec, $pipes);
        if (is_resource($proc)) {
            stream_set_blocking($pipes[1], false);
            $processes[$id] = [
                'proc'  => $proc,
                'pipes' => $pipes,
                'ip'    => $ip,
            ];
        }
    }

    $results = [];
    $start = time();
    while (!empty($processes)) {
        foreach ($processes as $id => $p) {
            $status = proc_get_status($p['proc']);
            if (!$status['running']) {
                $output = stream_get_contents($p['pipes'][1]);
                fclose($p['pipes'][1]);
                fclose($p['pipes'][2]);
                proc_close($p['proc']);

                $times = [];
                if (preg_match_all('/time[=<]\s*(\d+)\s*ms/i', $output, $matches)) {
                    foreach ($matches[1] as $time) {
                        $times[] = (int)$time;
                    }
                }
                if (count($times) > 0) {
                    $avg = number_format(array_sum($times) / count($times), 2);
                    $statusLabel = 'online';
                } else {
                    $avg = 0;
                    $statusLabel = 'offline';
                }

                $results[$id] = [
                    'ip'      => $p['ip'],
                    'latency' => $avg,
                    'status'  => $statusLabel,
                ];

                unset($processes[$id]);
            }
        }
        usleep(100000); // 100ms delay

        if (time() - $start > 10) {
            foreach ($processes as $id => $p) {
                proc_terminate($p['proc']);
                $results[$id] = [
                    'ip'      => $p['ip'],
                    'latency' => 0,
                    'status'  => 'offline'
                ];
            }
            break;
        }
    }
    return $results;
}
?>
