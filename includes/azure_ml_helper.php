<?php
/**
 * Predictive analytics for the dashboard.
 * 1. When AZURE_ML_ENDPOINT and AZURE_ML_API_KEY are set, uses Azure ML real-time endpoint.
 * 2. Otherwise uses real database data (lab_orders, etc.) and a statistical forecast.
 * Demo data is only used when no DB connection or no historical data exists.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/config.php';
}

/**
 * Call Azure ML real-time endpoint.
 * Expects endpoint to accept: {"input_data": {"data": [ [features...] ]}}
 * and return e.g. {"predictions": [ [...] ]} or similar (model-dependent).
 *
 * @param array $input_data Feature rows for the model
 * @return array|null Parsed response or null on failure
 */
function callAzureMLEndpoint(array $input_data) {
    $endpoint = defined('AZURE_ML_ENDPOINT') ? AZURE_ML_ENDPOINT : '';
    $api_key = defined('AZURE_ML_API_KEY') ? AZURE_ML_API_KEY : '';
    if ($endpoint === '' || $api_key === '') {
        return null;
    }
    $url = rtrim($endpoint, '/');
    if (strpos($url, '/score') === false) {
        $url .= '/score';
    }
    $payload = [
        'input_data' => [
            'data' => $input_data,
        ],
    ];
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key,
    ];
    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $headers),
            'content' => json_encode($payload),
            'timeout' => 15,
        ],
    ]);
    $response = @file_get_contents($url, false, $ctx);
    if ($response === false) {
        return null;
    }
    $decoded = json_decode($response, true);
    return is_array($decoded) ? $decoded : null;
}

/**
 * Fetch last 7 days of lab orders and patient volume from the database (real-time).
 * Returns [labels[7], patient_volume[7], lab_orders[7]] or null only when DB is unavailable.
 * Missing days are always filled with 0 so the chart has 7 points; empty table = zeros (real-time "no data yet").
 */
function getHistoricalMetricsFromDb() {
    $db = $GLOBALS['db'] ?? null;
    if (!$db) {
        return null;
    }
    $dayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    $byDate = [];
    try {
        $stmt = $db->query("
            SELECT DATE(order_time) AS d,
                   COUNT(*) AS lab_count,
                   COUNT(DISTINCT patient_id) AS patient_count
            FROM lab_orders
            WHERE order_time >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(order_time)
            ORDER BY d
        ");
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        foreach ($rows as $r) {
            $byDate[$r['d']] = [(int) $r['patient_count'], (int) $r['lab_count']];
        }
    } catch (Throwable $e) {
        return null;
    }
    $labels = [];
    $patient_volume = [];
    $lab_orders = [];
    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $labels[] = $dayNames[(int) date('N', strtotime($d)) - 1];
        $v = $byDate[$d] ?? [0, 0];
        $patient_volume[] = $v[0];
        $lab_orders[] = $v[1];
    }
    return [$labels, $patient_volume, $lab_orders];
}

/**
 * Real-time statistical forecast: weighted recent average + trend.
 * Weights last 3 days more heavily for a responsive, real-time feel.
 * Returns [forecast_patients[], forecast_lab[]] (7 values each).
 */
function computeStatisticalForecast(array $historical_patients, array $historical_lab) {
    $n = max(1, count($historical_patients));
    $m = max(1, count($historical_lab));
    $total_p = array_sum($historical_patients);
    $total_l = array_sum($historical_lab);
    $avg_p = $total_p / $n;
    $avg_l = $total_l / $m;
    $trend_p = 0.0;
    $trend_l = 0.0;
    if ($n >= 3) {
        $recent = array_slice($historical_patients, -3);
        $older = array_slice($historical_patients, 0, max(1, $n - 3));
        $avg_recent_p = array_sum($recent) / count($recent);
        $avg_older_p = array_sum($older) / count($older);
        $trend_p = $avg_recent_p - $avg_older_p;
    }
    if ($m >= 3) {
        $recent_l = array_slice($historical_lab, -3);
        $older_l = array_slice($historical_lab, 0, max(1, $m - 3));
        $trend_l = (array_sum($recent_l) / count($recent_l)) - (array_sum($older_l) / count($older_l));
    }
    $forecast_p = [];
    $forecast_l = [];
    for ($i = 0; $i < 7; $i++) {
        $forecast_p[] = (int) round(max(0, $avg_p + $trend_p * ($i + 1) * 0.4));
        $forecast_l[] = (int) round(max(0, $avg_l + $trend_l * ($i + 1) * 0.4));
    }
    return [$forecast_p, $forecast_l];
}

/**
 * Get predictive analytics data for the dashboard.
 * Uses Azure ML when configured; otherwise real DB + statistical forecast; fallback demo.
 *
 * @return array {
 *   source: 'azure_ml'|'database'|'demo',
 *   last_updated: string (ISO datetime),
 *   labels_historical: string[], labels_forecast: string[],
 *   historical_patient_volume: int[], forecast_patient_volume: int[],
 *   historical_lab_orders: int[], forecast_lab_orders: int[],
 *   summary: array { avg_patients_7d, avg_lab_orders_7d, trend },
 *   no_data_yet: bool
 * }
 */
function getPredictiveAnalyticsData() {
    $now = date('c');
    $forecast_days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    $demo_historical_days = $forecast_days;
    $demo_historical_patients = [0, 0, 0, 0, 0, 0, 0];
    $demo_historical_lab = [0, 0, 0, 0, 0, 0, 0];

    $azure_enabled = (defined('AZURE_ML_ENDPOINT') && AZURE_ML_ENDPOINT !== '' && defined('AZURE_ML_API_KEY') && AZURE_ML_API_KEY !== '');

    if ($azure_enabled) {
        $historical = getHistoricalMetricsFromDb();
        $hist_patients = $historical ? $historical[1] : $demo_historical_patients;
        $hist_lab = $historical ? $historical[2] : $demo_historical_lab;
        $input_rows = [];
        for ($i = 0; $i < 7; $i++) {
            $input_rows[] = [
                (float) date('N'),
                (float) ($i + 1),
                (float) ($hist_patients[$i] ?? 0),
                (float) ($hist_lab[$i] ?? 0),
            ];
        }
        $result = callAzureMLEndpoint($input_rows);
        if ($result !== null && isset($result['predictions'])) {
            $preds = $result['predictions'];
            if (isset($preds[0]) && is_array($preds[0])) {
                $forecast_patients = array_map(function ($row) {
                    return (int) round(is_array($row) ? ($row[0] ?? $row['patient_volume'] ?? 0) : $row);
                }, array_slice($preds, 0, 7));
                $forecast_lab = array_slice($preds, 0, 7);
                if (isset($forecast_lab[0]) && (is_array($forecast_lab[0]) && count($forecast_lab[0]) > 1)) {
                    $forecast_lab = array_map(function ($row) {
                        return (int) round(is_array($row) ? ($row[1] ?? $row['lab_orders'] ?? 0) : $row);
                    }, $forecast_lab);
                } else {
                    $forecast_lab = array_map(function ($v) {
                        return (int) round(is_array($v) ? ($v[0] ?? 0) : $v);
                    }, $forecast_lab);
                }
            } else {
                $forecast_patients = array_map(function ($v) {
                    return (int) round(is_numeric($v) ? $v : 0);
                }, array_slice($preds, 0, 7));
                list(, $forecast_lab) = computeStatisticalForecast($hist_patients, $hist_lab);
            }
            $labels_hist = $historical ? $historical[0] : $demo_historical_days;
            $avg_p = count($forecast_patients) ? array_sum($forecast_patients) / count($forecast_patients) : 0;
            $avg_l = count($forecast_lab) ? array_sum($forecast_lab) / count($forecast_lab) : 0;
            return [
                'source' => 'azure_ml',
                'last_updated' => $now,
                'labels_historical' => $labels_hist,
                'labels_forecast' => $forecast_days,
                'historical_patient_volume' => $hist_patients,
                'forecast_patient_volume' => $forecast_patients,
                'historical_lab_orders' => $hist_lab,
                'forecast_lab_orders' => $forecast_lab,
                'summary' => [
                    'avg_patients_7d' => (int) round($avg_p),
                    'avg_lab_orders_7d' => (int) round($avg_l),
                    'trend' => ($forecast_patients[6] ?? 0) >= ($hist_patients[6] ?? 0) ? 'up' : 'down',
                ],
                'no_data_yet' => false,
            ];
        }
    }

    $historical = getHistoricalMetricsFromDb();
    if ($historical !== null) {
        list($labels_hist, $hist_patients, $hist_lab) = $historical;
        $no_data_yet = (array_sum($hist_patients) === 0 && array_sum($hist_lab) === 0);

        // When DB has no lab orders yet, use sample demo data so dashboard shows meaningful preview
        if ($no_data_yet) {
            $hist_patients = [42, 48, 45, 52, 55, 50, 47];
            $hist_lab = [68, 72, 65, 78, 85, 72, 70];
        }
        list($forecast_patients, $forecast_lab) = computeStatisticalForecast($hist_patients, $hist_lab);
        $avg_p = count($forecast_patients) ? array_sum($forecast_patients) / count($forecast_patients) : 0;
        $avg_l = count($forecast_lab) ? array_sum($forecast_lab) / count($forecast_lab) : 0;
        $last_hist_p = $hist_patients[count($hist_patients) - 1] ?? 0;
        $last_forecast_p = $forecast_patients[6] ?? 0;
        $trend = $no_data_yet ? 'up' : ($last_forecast_p >= $last_hist_p ? 'up' : 'down');
        return [
            'source' => $no_data_yet ? 'demo' : 'database',
            'last_updated' => $now,
            'labels_historical' => $labels_hist,
            'labels_forecast' => $forecast_days,
            'historical_patient_volume' => $hist_patients,
            'forecast_patient_volume' => $forecast_patients,
            'historical_lab_orders' => $hist_lab,
            'forecast_lab_orders' => $forecast_lab,
            'summary' => [
                'avg_patients_7d' => (int) round($avg_p),
                'avg_lab_orders_7d' => (int) round($avg_l),
                'trend' => $trend,
            ],
            'no_data_yet' => $no_data_yet,
        ];
    }

    // Use realistic sample data when DB has no data yet - dashboard shows meaningful preview
    $sample_historical_patients = [42, 48, 45, 52, 55, 50, 47];
    $sample_historical_lab = [68, 72, 65, 78, 85, 72, 70];
    list($sample_forecast_p, $sample_forecast_l) = computeStatisticalForecast($sample_historical_patients, $sample_historical_lab);

    return [
        'source' => 'demo',
        'last_updated' => $now,
        'labels_historical' => $demo_historical_days,
        'labels_forecast' => $forecast_days,
        'historical_patient_volume' => $sample_historical_patients,
        'forecast_patient_volume' => $sample_forecast_p,
        'historical_lab_orders' => $sample_historical_lab,
        'forecast_lab_orders' => $sample_forecast_l,
        'summary' => [
            'avg_patients_7d' => (int) round(array_sum($sample_forecast_p) / max(1, count($sample_forecast_p))),
            'avg_lab_orders_7d' => (int) round(array_sum($sample_forecast_l) / max(1, count($sample_forecast_l))),
            'trend' => 'up',
        ],
        'no_data_yet' => true,
    ];
}
