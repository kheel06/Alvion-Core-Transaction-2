<?php
/**
 * Doctor Portal – real report data from hospital database (Philippines HMS).
 * Used by Reports > Productivity and Clinical Quality. No mock/sample data.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/config.php';
}

/**
 * Get productivity stats from real DB for the logged-in doctor.
 * Returns array with keys: encounters_this_month, notes_signed, rvus_mtd, outpatient_visits, inpatient_days, source ('database'|'none').
 */
function getDoctorProductivityFromDb() {
    global $db;
    $doctor_id = (int)($_SESSION['user_id'] ?? 0);
    if (!$doctor_id || !$db) {
        return ['encounters_this_month' => 0, 'notes_signed' => 0, 'rvus_mtd' => 0, 'outpatient_visits' => 0, 'inpatient_days' => 0, 'source' => 'none'];
    }
    $month_start = date('Y-m-01');
    $month_end = date('Y-m-t');
    try {
        $stmt = $db->prepare("
            SELECT
                COUNT(*) as encounters_this_month,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_visits
            FROM appointments
            WHERE doctor_id = :doctor_id
              AND appointment_date BETWEEN :month_start AND :month_end
        ");
        $stmt->execute([':doctor_id' => $doctor_id, ':month_start' => $month_start, ':month_end' => $month_end]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $encounters = (int)($row['encounters_this_month'] ?? 0);
        $outpatient = (int)($row['completed_visits'] ?? 0);
        return [
            'encounters_this_month' => $encounters,
            'notes_signed' => 0,
            'rvus_mtd' => 0,
            'outpatient_visits' => $outpatient,
            'inpatient_days' => 0,
            'source' => 'database',
        ];
    } catch (PDOException $e) {
        return ['encounters_this_month' => 0, 'notes_signed' => 0, 'rvus_mtd' => 0, 'outpatient_visits' => 0, 'inpatient_days' => 0, 'source' => 'none'];
    }
}

/**
 * Get quality metrics from real DB if available.
 * Returns array of [['metric' => string, 'rate' => int, 'target' => int], ...] and 'source' => 'database'|'none'.
 */
function getDoctorQualityFromDb() {
    global $db;
    $doctor_id = (int)($_SESSION['user_id'] ?? 0);
    if (!$doctor_id || !$db) {
        return ['metrics' => [], 'source' => 'none'];
    }
    try {
        $tables = $db->query("SHOW TABLES LIKE 'quality_metrics'")->rowCount();
        if ($tables > 0) {
            $stmt = $db->prepare("SELECT metric_name as metric, rate, target FROM quality_metrics WHERE doctor_id = :doctor_id ORDER BY metric_name");
            $stmt->execute([':doctor_id' => $doctor_id]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $metrics = [];
            foreach ($rows as $r) {
                $metrics[] = ['metric' => $r['metric'] ?? '', 'rate' => (int)($r['rate'] ?? 0), 'target' => (int)($r['target'] ?? 0)];
            }
            return ['metrics' => $metrics, 'source' => 'database'];
        }
    } catch (PDOException $e) {
        // ignore
    }
    return ['metrics' => [], 'source' => 'none'];
}
