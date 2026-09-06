<?php
/**
 * Seed surgery_schedule for a given week (real data, Philippine hospital).
 * Used by run_migrations_and_seeders.php so the Surgery Schedule & Block Time page shows data.
 * Call: seed_sors_schedule_week($pdo) or seed_sors_schedule_week($pdo, $startDate, $endDate).
 *
 * @param PDO $pdo
 * @param string|null $startDate Y-m-d (Monday of week). Default: week of 2026-02-02.
 * @param string|null $endDate   Y-m-d (Sunday of week). Default: 2026-02-08.
 * @return array{inserted:int, errors:array}
 */
function seed_sors_schedule_week(PDO $pdo, $startDate = null, $endDate = null) {
    $startDate = $startDate ?? '2026-02-02';
    $endDate   = $endDate   ?? '2026-02-08';

    $out = ['inserted' => 0, 'errors' => []];

    try {
        $surgeons = $pdo->query("SELECT id, employee_id FROM surgeons WHERE status = 'active' ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
        $procs    = $pdo->query("SELECT id, procedure_code FROM procedure_catalog ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
        $anes     = $pdo->query("SELECT id, employee_id FROM anesthesiologists WHERE status = 'active' ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
        $ors      = $pdo->query("SELECT id, room_number FROM operating_rooms WHERE status IN ('Ready', 'In Use') ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $out['errors'][] = 'Lookup tables: ' . $e->getMessage();
        return $out;
    }

    if (empty($surgeons) || empty($procs) || empty($ors)) {
        $out['errors'][] = 'Need surgeons, procedure_catalog, and operating_rooms. Run SORS database_setup.sql first.';
        return $out;
    }

    $getProcId = function ($code) use ($procs) {
        foreach ($procs as $p) if (($p['procedure_code'] ?? '') === $code) return (int)$p['id'];
        return null;
    };
    $getSurgeonId = function ($empId) use ($surgeons) {
        foreach ($surgeons as $s) if (($s['employee_id'] ?? '') === $empId) return (int)$s['id'];
        return $surgeons[0]['id'] ?? null;
    };
    $getAnesId = function ($empId) use ($anes) {
        foreach ($anes as $a) if (($a['employee_id'] ?? '') === $empId) return (int)$a['id'];
        return !empty($anes) ? (int)$anes[0]['id'] : null;
    };
    $getOrId = function ($room) use ($ors) {
        foreach ($ors as $o) if (($o['room_number'] ?? '') === $room) return (int)$o['id'];
        return (int)$ors[0]['id'];
    };

    $rows = [
        ['P-2026-001', 'Rosa Almario', 'APP-001', 'EMP-SURG-004', 'EMP-ANES-003', 'OR-01', '2026-02-02', '08:00:00', '09:00:00', 'Scheduled', 'Routine', 60],
        ['P-2026-002', 'Emilio Santos Jr.', 'CHOL-001', 'EMP-SURG-007', 'EMP-ANES-006', 'OR-02', '2026-02-02', '09:00:00', '10:30:00', 'Scheduled', 'Routine', 90],
        ['P-2026-003', 'Lorna Dimaguiba', 'HERN-001', 'EMP-SURG-004', 'EMP-ANES-006', 'OR-01', '2026-02-02', '10:00:00', '11:00:00', 'Scheduled', 'Routine', 60],
        ['P-2026-004', 'Gregorio Villanueva', 'CRAN-001', 'EMP-SURG-002', 'EMP-ANES-004', 'OR-05', '2026-02-02', '08:00:00', '11:00:00', 'Scheduled', 'Routine', 180],
        ['P-2026-005', 'Cecilia Bautista', 'HIP-001', 'EMP-SURG-003', 'EMP-ANES-003', 'OR-07', '2026-02-03', '08:00:00', '10:00:00', 'Scheduled', 'Routine', 120],
        ['P-2026-006', 'Arturo Reyes', 'APP-001', 'EMP-SURG-007', 'EMP-ANES-003', 'OR-01', '2026-02-03', '10:30:00', '11:30:00', 'In Progress', 'Urgent', 60],
        ['P-2026-007', 'Imelda Cruz', 'THY-001', 'EMP-SURG-004', 'EMP-ANES-006', 'OR-02', '2026-02-03', '09:00:00', '10:30:00', 'Scheduled', 'Routine', 90],
        ['P-2026-008', 'Rodrigo Mendoza', 'CABG-001', 'EMP-SURG-001', 'EMP-ANES-001', 'OR-03', '2026-02-04', '07:00:00', '11:00:00', 'Scheduled', 'Routine', 240],
        ['P-2026-009', 'Aurora Santiago', 'KNEE-001', 'EMP-SURG-003', 'EMP-ANES-003', 'OR-07', '2026-02-04', '08:00:00', '09:45:00', 'Scheduled', 'Routine', 105],
        ['P-2026-010', 'Benito Lopez', 'CHOL-001', 'EMP-SURG-004', 'EMP-ANES-006', 'OR-01', '2026-02-04', '14:00:00', '15:30:00', 'Scheduled', 'Routine', 90],
        ['P-2026-011', 'Corazon Abad', 'CS-001', 'EMP-SURG-004', 'EMP-ANES-002', 'OR-01', '2026-02-05', '08:00:00', '09:00:00', 'Scheduled', 'Routine', 60],
        ['P-2026-012', 'Felipe Navarro', 'SPINE-001', 'EMP-SURG-008', 'EMP-ANES-004', 'OR-05', '2026-02-05', '08:00:00', '11:30:00', 'Scheduled', 'Routine', 210],
        ['P-2026-013', 'Gloria Estrella', 'PED-001', 'EMP-SURG-005', 'EMP-ANES-002', 'OR-08', '2026-02-05', '10:00:00', '11:15:00', 'Scheduled', 'Routine', 75],
        ['P-2026-014', 'Hector dela Rosa', 'CATH-001', 'EMP-SURG-001', 'EMP-ANES-001', 'OR-03', '2026-02-06', '08:00:00', '09:30:00', 'Scheduled', 'Routine', 90],
        ['P-2026-015', 'Irene Tan', 'HERN-001', 'EMP-SURG-007', 'EMP-ANES-006', 'OR-02', '2026-02-06', '09:00:00', '10:00:00', 'Scheduled', 'Routine', 60],
        ['P-2026-016', 'Jose Maria Flores', 'HIP-001', 'EMP-SURG-003', 'EMP-ANES-003', 'OR-07', '2026-02-06', '11:00:00', '13:00:00', 'Scheduled', 'Routine', 120],
        ['P-2026-017', 'Kristina Morales', 'APP-001', 'EMP-SURG-004', 'EMP-ANES-003', 'OR-10', '2026-02-07', '09:00:00', '10:00:00', 'Scheduled', 'Urgent', 60],
        ['P-2026-018', 'Leonardo Gutierrez', 'APP-001', 'EMP-SURG-007', 'EMP-ANES-003', 'OR-10', '2026-02-08', '10:00:00', '11:00:00', 'Scheduled', 'Emergency', 60],
    ];

    try {
        $pdo->exec("DELETE FROM surgery_schedule WHERE scheduled_date BETWEEN " . $pdo->quote($startDate) . " AND " . $pdo->quote($endDate));
    } catch (PDOException $e) {
        $out['errors'][] = 'Delete: ' . $e->getMessage();
        return $out;
    }

    $sql = "INSERT INTO surgery_schedule (patient_id, patient_name, procedure_id, surgeon_id, anesthesiologist_id, or_id, block_id, scheduled_date, scheduled_start_time, scheduled_end_time, status, priority, estimated_duration, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, NULL, NULL)";
    $stmt = $pdo->prepare($sql);

    foreach ($rows as $r) {
        $procId = $getProcId($r[2]);
        $surgeonId = $getSurgeonId($r[3]);
        $anesId = $getAnesId($r[4]);
        $orId = $getOrId($r[5]);
        if ($procId === null || $surgeonId === null || $orId === null) {
            $out['errors'][] = "Skip row {$r[0]}: missing procedure/surgeon/OR";
            continue;
        }
        try {
            $stmt->execute([
                $r[0], $r[1], $procId, $surgeonId, $anesId, $orId,
                $r[6], $r[7], $r[8], $r[9], $r[10], $r[11]
            ]);
            $out['inserted']++;
        } catch (PDOException $e) {
            $out['errors'][] = $r[0] . ': ' . $e->getMessage();
        }
    }

    return $out;
}
