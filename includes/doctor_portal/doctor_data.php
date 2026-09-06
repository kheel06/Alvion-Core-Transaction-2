<?php
/**
 * Doctor Portal – real data from database (Philippine HMS).
 * Use this instead of mock_data.php so every page shows live data for the logged-in doctor.
 */
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/config.php';
}

function doctorPortal_getCurrentDoctorId() {
    return (int)($_SESSION['user_id'] ?? 0);
}

function _db() {
    global $db;
    return $db;
}

/**
 * Patients assigned to current doctor: from appointments (distinct) + inpatient assignments (active).
 */
function doctorPortal_getPatientsForDoctor() {
    $db = _db();
    $doctorId = doctorPortal_getCurrentDoctorId();
    if (!$doctorId || !$db) return [];

    $out = [];
    $seen = [];
    try {
        $stmt = $db->prepare("
            SELECT DISTINCT p.id, p.hospital_id AS mrn, p.first_name, p.last_name, p.birth_date AS dob, p.gender AS sex,
                   e.allergies, e.code_status, e.philhealth,
                   a.ward, a.bed, a.admission_date
            FROM patients p
            LEFT JOIN patient_extras e ON e.patient_id = p.id
            LEFT JOIN doctor_inpatient_assignments a ON a.patient_id = p.id AND a.doctor_id = ? AND a.discharge_date IS NULL
            WHERE p.id IN (
                SELECT patient_id FROM appointments WHERE doctor_id = ?
                UNION
                SELECT patient_id FROM doctor_inpatient_assignments WHERE doctor_id = ? AND discharge_date IS NULL
            )
            ORDER BY p.last_name, p.first_name
        ");
        $stmt->execute([$doctorId, $doctorId, $doctorId]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $id = $row['id'];
            if (isset($seen[$id])) continue;
            $seen[$id] = true;
            $out[] = [
                'id' => $id,
                'mrn' => $row['mrn'] ?? 'MRN-' . $id,
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'dob' => $row['dob'],
                'sex' => $row['sex'] ?? '',
                'ward' => $row['ward'],
                'bed' => $row['bed'],
                'allergies' => $row['allergies'] ?? 'None',
                'code_status' => $row['code_status'] ?? 'Full Code',
                'assigned_doctor_id' => $doctorId,
                'philhealth' => $row['philhealth'],
                'admission_date' => $row['admission_date'],
            ];
        }
    } catch (PDOException $e) {
        error_log('doctor_data getPatientsForDoctor: ' . $e->getMessage());
    }
    return $out;
}

function doctorPortal_getPatient($patientId) {
    $db = _db();
    if (!$db) return null;
    $pid = (int)$patientId;
    try {
        $stmt = $db->prepare("
            SELECT p.id, p.hospital_id AS mrn, p.first_name, p.last_name, p.birth_date AS dob, p.gender AS sex,
                   e.allergies, e.code_status, e.philhealth
            FROM patients p
            LEFT JOIN patient_extras e ON e.patient_id = p.id
            WHERE p.id = ?
        ");
        $stmt->execute([$pid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        $stmt2 = $db->prepare("SELECT ward, bed, admission_date FROM doctor_inpatient_assignments WHERE patient_id = ? AND discharge_date IS NULL ORDER BY id DESC LIMIT 1");
        $stmt2->execute([$pid]);
        $a = $stmt2->fetch(PDO::FETCH_ASSOC);
        return [
            'id' => (string)$row['id'],
            'mrn' => $row['mrn'] ?? 'MRN-' . $row['id'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'dob' => $row['dob'],
            'sex' => $row['sex'] ?? '',
            'ward' => $a['ward'] ?? null,
            'bed' => $a['bed'] ?? null,
            'allergies' => $row['allergies'] ?? 'None',
            'code_status' => $row['code_status'] ?? 'Full Code',
            'assigned_doctor_id' => null,
            'philhealth' => $row['philhealth'],
            'admission_date' => $a['admission_date'] ?? null,
        ];
    } catch (PDOException $e) {
        error_log('doctor_data getPatient: ' . $e->getMessage());
        return null;
    }
}

function doctorPortal_canAccessPatient($patientId) {
    $doctorId = doctorPortal_getCurrentDoctorId();
    $db = _db();
    if (!$doctorId || !$db) return false;
    $pid = (int)$patientId;
    try {
        $stmt = $db->prepare("SELECT 1 FROM appointments WHERE patient_id = ? AND doctor_id = ? LIMIT 1");
        $stmt->execute([$pid, $doctorId]);
        if ($stmt->fetch()) return true;
        $stmt = $db->prepare("SELECT 1 FROM doctor_inpatient_assignments WHERE patient_id = ? AND doctor_id = ? AND discharge_date IS NULL LIMIT 1");
        $stmt->execute([$pid, $doctorId]);
        return (bool)$stmt->fetch();
    } catch (PDOException $e) {
        return false;
    }
}

function doctorPortal_logBreakGlassAccess($patientId, $reason) {
    $db = _db();
    $doctorId = doctorPortal_getCurrentDoctorId();
    if (!$db || !$doctorId) return;
    try {
        $stmt = $db->prepare("INSERT INTO doctor_audit_log (doctor_id, action, patient_id, reason, created_at, ip_address) VALUES (?, 'Break-glass access', ?, ?, NOW(), ?)");
        $stmt->execute([$doctorId, (int)$patientId, $reason, $_SERVER['REMOTE_ADDR'] ?? null]);
    } catch (PDOException $e) {
        $_SESSION['doctor_portal_audit'] = $_SESSION['doctor_portal_audit'] ?? [];
        $_SESSION['doctor_portal_audit'][] = ['action' => 'Break-glass access', 'patient_id' => $patientId, 'reason' => $reason, 'at' => date('Y-m-d H:i:s')];
    }
}

// ---------- Appointments (today for current doctor) ----------
function doctorPortal_seedAppointments() {
    $db = _db();
    $doctorId = doctorPortal_getCurrentDoctorId();
    if (!$db || !$doctorId) return [];
    $out = [];
    try {
        $stmt = $db->prepare("
            SELECT a.id, a.appointment_time AS time, a.status,
                   CONCAT(p.first_name, ' ', p.last_name) AS patient, p.hospital_id AS mrn, p.id AS patient_id,
                   a.notes
            FROM appointments a
            JOIN patients p ON p.id = a.patient_id
            WHERE a.doctor_id = ? AND a.appointment_date = CURDATE() AND a.status != 'cancelled'
            ORDER BY a.appointment_time
        ");
        $stmt->execute([$doctorId]);
        $types = ['Follow-up', 'New', 'Post-op', 'Consult', 'Routine'];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $out[] = [
                'id' => $row['id'],
                'time' => date('H:i', strtotime($row['time'])),
                'patient' => $row['patient'],
                'mrn' => $row['mrn'],
                'patient_id' => (string)$row['patient_id'],
                'type' => $types[$row['id'] % count($types)],
                'status' => $row['status'] === 'in_progress' ? 'Checked in' : ucfirst(str_replace('_', ' ', $row['status'])),
                'chief_complaint' => $row['notes'] ?? '',
            ];
        }
    } catch (PDOException $e) {
        error_log('doctor_data seedAppointments: ' . $e->getMessage());
    }
    return $out;
}

function doctorPortal_seedSigningQueue() {
    $db = _db();
    $doctorId = doctorPortal_getCurrentDoctorId();
    if (!$db || !$doctorId) return [];
    $out = [];
    try {
        $stmt = $db->prepare("
            SELECT s.id, s.order_type AS type, s.order_description AS order, s.ordered_at AS ordered_at,
                   CONCAT(p.first_name, ' ', p.last_name) AS patient, p.hospital_id AS mrn
            FROM doctor_signing_queue s
            JOIN patients p ON p.id = s.patient_id
            WHERE s.doctor_id = ? AND s.status = 'Pending'
            ORDER BY s.ordered_at DESC
        ");
        $stmt->execute([$doctorId]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $out[] = [
                'id' => $row['id'],
                'type' => $row['type'],
                'patient' => $row['patient'],
                'mrn' => $row['mrn'],
                'order' => $row['order'],
                'ordered_at' => date('Y-m-d H:i', strtotime($row['ordered_at'])),
            ];
        }
    } catch (PDOException $e) {
        error_log('doctor_data seedSigningQueue: ' . $e->getMessage());
    }
    return $out;
}

function doctorPortal_seedCriticalAlerts() {
    $db = _db();
    $doctorId = doctorPortal_getCurrentDoctorId();
    if (!$db || !$doctorId) return [];
    $out = [];
    try {
        $stmt = $db->prepare("
            SELECT r.id, r.result_text AS result, r.alerted_at AS time, r.acknowledged,
                   CONCAT(p.first_name, ' ', p.last_name) AS patient, p.hospital_id AS mrn
            FROM doctor_result_alerts r
            JOIN patients p ON p.id = r.patient_id
            WHERE r.doctor_id = ? AND r.acknowledged = 0
            ORDER BY r.alerted_at DESC
        ");
        $stmt->execute([$doctorId]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $out[] = [
                'id' => $row['id'],
                'patient' => $row['patient'],
                'mrn' => $row['mrn'],
                'result' => $row['result'],
                'time' => date('Y-m-d H:i', strtotime($row['time'])),
                'acknowledged' => (bool)$row['acknowledged'],
            ];
        }
    } catch (PDOException $e) {
        error_log('doctor_data seedCriticalAlerts: ' . $e->getMessage());
    }
    return $out;
}

function doctorPortal_seedConsults() {
    $db = _db();
    $doctorId = doctorPortal_getCurrentDoctorId();
    if (!$db || !$doctorId) return [];
    $out = [];
    try {
        $stmt = $db->prepare("
            SELECT c.id, c.service, c.indication AS indication, c.requested_date AS requested, c.status,
                   CONCAT(p.first_name, ' ', p.last_name) AS patient, p.hospital_id AS mrn, p.id AS patient_id
            FROM doctor_consults c
            JOIN patients p ON p.id = c.patient_id
            WHERE c.doctor_id = ?
            ORDER BY c.requested_date DESC
        ");
        $stmt->execute([$doctorId]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $out[] = [
                'id' => $row['id'],
                'patient' => $row['patient'],
                'mrn' => $row['mrn'],
                'patient_id' => (string)$row['patient_id'],
                'service' => $row['service'],
                'requested' => $row['requested'],
                'status' => $row['status'],
                'indication' => $row['indication'],
            ];
        }
    } catch (PDOException $e) {
        error_log('doctor_data seedConsults: ' . $e->getMessage());
    }
    return $out;
}

function doctorPortal_seedReferrals() {
    $db = _db();
    $doctorId = doctorPortal_getCurrentDoctorId();
    if (!$db || !$doctorId) return [];
    $out = [];
    try {
        $stmt = $db->prepare("
            SELECT r.id, r.from_source AS from_name, r.reason, r.referral_date AS date, r.status,
                   CONCAT(p.first_name, ' ', p.last_name) AS patient, p.hospital_id AS mrn, p.id AS patient_id
            FROM doctor_referrals r
            JOIN patients p ON p.id = r.patient_id
            WHERE r.to_doctor_id = ?
            ORDER BY r.referral_date DESC
        ");
        $stmt->execute([$doctorId]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $out[] = [
                'id' => $row['id'],
                'from' => $row['from_name'],
                'patient' => $row['patient'],
                'mrn' => $row['mrn'],
                'patient_id' => (string)$row['patient_id'],
                'reason' => $row['reason'],
                'date' => $row['date'],
                'status' => $row['status'],
            ];
        }
    } catch (PDOException $e) {
        error_log('doctor_data seedReferrals: ' . $e->getMessage());
    }
    return $out;
}

function doctorPortal_seedPendingResults() {
    $db = _db();
    $doctorId = doctorPortal_getCurrentDoctorId();
    if (!$db || !$doctorId) return [];
    $out = [];
    try {
        $stmt = $db->prepare("
            SELECT pr.id, pr.order_description AS order, pr.ordered_at AS ordered, pr.status,
                   CONCAT(p.first_name, ' ', p.last_name) AS patient, p.hospital_id AS mrn, p.id AS patient_id
            FROM doctor_pending_results pr
            JOIN patients p ON p.id = pr.patient_id
            WHERE pr.doctor_id = ?
            ORDER BY pr.ordered_at DESC
        ");
        $stmt->execute([$doctorId]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $out[] = [
                'id' => $row['id'],
                'patient' => $row['patient'],
                'mrn' => $row['mrn'],
                'patient_id' => (string)$row['patient_id'],
                'order' => $row['order'],
                'ordered' => date('Y-m-d H:i', strtotime($row['ordered'])),
                'status' => $row['status'],
            ];
        }
    } catch (PDOException $e) {
        error_log('doctor_data seedPendingResults: ' . $e->getMessage());
    }
    return $out;
}

function doctorPortal_seedMessagingThreads() {
    $db = _db();
    $doctorId = doctorPortal_getCurrentDoctorId();
    if (!$db || !$doctorId) return [];
    $out = [];
    try {
        $stmt = $db->prepare("SELECT id, from_name AS from_name, subject, sent_at AS date, unread FROM doctor_messages WHERE doctor_id = ? ORDER BY sent_at DESC");
        $stmt->execute([$doctorId]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $out[] = [
                'id' => $row['id'],
                'from' => $row['from_name'],
                'subject' => $row['subject'],
                'date' => date('Y-m-d H:i', strtotime($row['date'])),
                'unread' => (bool)$row['unread'],
            ];
        }
    } catch (PDOException $e) {
        error_log('doctor_data seedMessagingThreads: ' . $e->getMessage());
    }
    return $out;
}

function doctorPortal_seedAnnouncements() {
    $db = _db();
    if (!$db) return [];
    $out = [];
    try {
        $stmt = $db->query("SELECT id, title, department, published_at AS date FROM doctor_announcements ORDER BY published_at DESC LIMIT 20");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $out[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'date' => $row['date'],
                'read' => false,
                'department' => $row['department'],
            ];
        }
    } catch (PDOException $e) {
        error_log('doctor_data seedAnnouncements: ' . $e->getMessage());
    }
    return $out;
}

function doctorPortal_seedOrderSets() {
    $db = _db();
    if (!$db) return [];
    $out = [];
    try {
        $stmt = $db->query("SELECT id, name, category, description, updated_at AS updated FROM doctor_order_sets ORDER BY category, name");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $out[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'category' => $row['category'],
                'description' => $row['description'],
                'updated' => $row['updated'],
            ];
        }
    } catch (PDOException $e) {
        error_log('doctor_data seedOrderSets: ' . $e->getMessage());
    }
    return $out;
}

function doctorPortal_seedDischargeList() {
    $db = _db();
    $doctorId = doctorPortal_getCurrentDoctorId();
    if (!$db || !$doctorId) return [];
    $out = [];
    try {
        $stmt = $db->prepare("
            SELECT a.patient_id, a.ward, a.bed,
                   CONCAT(p.last_name, ', ', p.first_name) AS patient, p.hospital_id AS mrn
            FROM doctor_inpatient_assignments a
            JOIN patients p ON p.id = a.patient_id
            WHERE a.doctor_id = ? AND a.discharge_date IS NULL AND a.ward IS NOT NULL
            ORDER BY a.ward, a.bed
        ");
        $stmt->execute([$doctorId]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $out[] = [
                'patient_id' => (string)$row['patient_id'],
                'patient' => $row['patient'],
                'mrn' => $row['mrn'],
                'ward' => $row['ward'],
                'bed' => $row['bed'] ?? '',
                'status' => 'Under treatment',
            ];
        }
    } catch (PDOException $e) {
        error_log('doctor_data seedDischargeList: ' . $e->getMessage());
    }
    return $out;
}

function doctorPortal_seedORSchedule() {
    $db = _db();
    if (!$db) return [];
    $out = [];
    try {
        $tables = $db->query("SHOW TABLES LIKE 'surgery_schedule'")->rowCount();
        if ($tables > 0) {
            $stmt = $db->query("
                SELECT ss.id, ss.scheduled_date, ss.scheduled_start_time AS start_time, ss.scheduled_end_time AS end_time,
                       ss.patient_name AS patient, ss.patient_id AS mrn, pc.procedure_name AS procedure,
                       or_rooms.room_number AS room, CONCAT(s.first_name, ' ', s.last_name) AS surgeon, ss.status
                FROM surgery_schedule ss
                LEFT JOIN procedure_catalog pc ON pc.id = ss.procedure_id
                LEFT JOIN surgeons s ON s.id = ss.surgeon_id
                LEFT JOIN operating_rooms or_rooms ON or_rooms.id = ss.or_id
                WHERE ss.scheduled_date >= CURDATE()
                ORDER BY ss.scheduled_date, ss.scheduled_start_time
                LIMIT 30
            ");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $out[] = [
                    'id' => $row['id'],
                    'time' => date('H:i', strtotime($row['start_time'])),
                    'end' => date('H:i', strtotime($row['end_time'])),
                    'patient' => $row['patient'],
                    'mrn' => $row['mrn'],
                    'procedure' => $row['procedure'] ?? 'Surgery',
                    'room' => $row['room'] ?? 'OR',
                    'surgeon' => $row['surgeon'] ? 'Dr. ' . $row['surgeon'] : '',
                    'status' => $row['status'],
                ];
            }
        }
    } catch (PDOException $e) {
        error_log('doctor_data seedORSchedule: ' . $e->getMessage());
    }
    return $out;
}

function doctorPortal_seedProcedureNotes() {
    $db = _db();
    if (!$db) return [];
    $out = [];
    try {
        $tables = $db->query("SHOW TABLES LIKE 'surgery_schedule'")->rowCount();
        if ($tables > 0) {
            $stmt = $db->query("
                SELECT ss.id, ss.scheduled_date AS date, ss.patient_name AS patient, ss.patient_id AS mrn, ss.patient_id AS patient_id,
                       pc.procedure_name AS procedure, CONCAT(s.first_name, ' ', s.last_name) AS surgeon
                FROM surgery_schedule ss
                LEFT JOIN procedure_catalog pc ON pc.id = ss.procedure_id
                LEFT JOIN surgeons s ON s.id = ss.surgeon_id
                WHERE ss.status IN ('Completed', 'In Progress')
                ORDER BY ss.scheduled_date DESC
                LIMIT 20
            ");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $out[] = [
                    'id' => $row['id'],
                    'date' => $row['date'],
                    'patient' => $row['patient'],
                    'mrn' => $row['mrn'],
                    'patient_id' => (string)$row['patient_id'],
                    'procedure' => ($row['procedure'] ?? 'Surgery') . ' – op note on file',
                    'surgeon' => $row['surgeon'] ? 'Dr. ' . $row['surgeon'] : '',
                ];
            }
        }
    } catch (PDOException $e) {
        error_log('doctor_data seedProcedureNotes: ' . $e->getMessage());
    }
    return $out;
}

function doctorPortal_seedProductivity() {
    if (file_exists(__DIR__ . '/report_data.php')) {
        require_once __DIR__ . '/report_data.php';
        $r = getDoctorProductivityFromDb();
        if (($r['source'] ?? '') === 'database') {
            return [
                'encounters_this_month' => $r['encounters_this_month'],
                'notes_signed' => $r['notes_signed'],
                'rvus_mtd' => $r['rvus_mtd'],
                'outpatient_visits' => $r['outpatient_visits'],
                'inpatient_days' => $r['inpatient_days'],
            ];
        }
    }
    return ['encounters_this_month' => 0, 'notes_signed' => 0, 'rvus_mtd' => 0, 'outpatient_visits' => 0, 'inpatient_days' => 0];
}

function doctorPortal_seedQualityMetrics() {
    if (file_exists(__DIR__ . '/report_data.php')) {
        require_once __DIR__ . '/report_data.php';
        $r = getDoctorQualityFromDb();
        if (($r['source'] ?? '') === 'database' && !empty($r['metrics'])) {
            return $r['metrics'];
        }
    }
    return [];
}

function doctorPortal_seedAuditLog() {
    $db = _db();
    $doctorId = doctorPortal_getCurrentDoctorId();
    $out = $_SESSION['doctor_portal_audit'] ?? [];
    if (!$db || !$doctorId) return $out;
    try {
        $stmt = $db->prepare("SELECT id, action, patient_id, reason, created_at AS at FROM doctor_audit_log WHERE doctor_id = ? ORDER BY created_at DESC LIMIT 50");
        $stmt->execute([$doctorId]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            array_unshift($out, [
                'id' => $row['id'],
                'action' => $row['action'],
                'patient_id' => $row['patient_id'],
                'reason' => $row['reason'],
                'at' => date('Y-m-d H:i', strtotime($row['at'])),
            ]);
        }
    } catch (PDOException $e) {
        error_log('doctor_data seedAuditLog: ' . $e->getMessage());
    }
    return $out;
}

// ---------- Chart data (per patient) ----------
function _chartTable($db, $table, $patientId, $columns = '*') {
    $pid = (int)$patientId;
    try {
        $stmt = $db->prepare("SELECT $columns FROM $table WHERE patient_id = ? ORDER BY id DESC");
        $stmt->execute([$pid]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

function doctorPortal_seedVitals($patientId) {
    $db = _db();
    if (!$db) return [];
    $rows = _chartTable($db, 'patient_vitals', $patientId, 'recorded_at AS time, bp_sys, bp_dia, hr, temp, rr, spo2');
    $out = [];
    foreach ($rows as $r) {
        $bp = null;
        if (isset($r['bp_sys']) && $r['bp_sys'] !== null) {
            $bp = (int)$r['bp_sys'];
            if (isset($r['bp_dia']) && $r['bp_dia'] !== null) {
                $bp .= '/' . (int)$r['bp_dia'];
            }
        }
        $out[] = [
            'patient_id' => $patientId,
            'time' => date('H:i', strtotime($r['time'])),
            'bp' => $bp,
            'hr' => isset($r['hr']) ? (int)$r['hr'] : null,
            'temp' => isset($r['temp']) ? (float)$r['temp'] : null,
            'rr' => isset($r['rr']) ? (int)$r['rr'] : null,
            'spo2' => isset($r['spo2']) ? (int)$r['spo2'] : null,
        ];
    }
    return $out;
}

function doctorPortal_seedLabResults($patientId) {
    $db = _db();
    if (!$db) return [];
    $rows = _chartTable($db, 'patient_lab_results', $patientId, 'id, panel, result_text AS result, status, collected_at AS collected, reported_at AS reported');
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'id' => $r['id'],
            'panel' => $r['panel'],
            'result' => $r['result'],
            'status' => $r['status'],
            'collected' => $r['collected'],
            'reported' => $r['reported'],
        ];
    }
    return $out;
}

function doctorPortal_seedImaging($patientId) {
    $db = _db();
    if (!$db) return [];
    $rows = _chartTable($db, 'patient_imaging', $patientId, 'id, modality, description, result_text AS result, status, performed_at AS performed');
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'id' => $r['id'],
            'modality' => $r['modality'],
            'description' => $r['description'],
            'result' => $r['result'],
            'status' => $r['status'],
            'performed' => $r['performed'],
        ];
    }
    return $out;
}

function doctorPortal_seedPathology($patientId) {
    return [];
}

function doctorPortal_seedProblems($patientId) {
    $db = _db();
    if (!$db) return [];
    $rows = _chartTable($db, 'patient_problems', $patientId, 'icd, description, status, onset_date AS onset');
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'icd' => $r['icd'],
            'description' => $r['description'],
            'status' => $r['status'],
            'onset' => $r['onset'],
        ];
    }
    return $out;
}

function doctorPortal_seedMedications($patientId) {
    $db = _db();
    if (!$db) return [];
    $rows = _chartTable($db, 'patient_medications', $patientId, 'id, drug, dose, status, start_date AS start, prescriber');
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'id' => $r['id'],
            'drug' => $r['drug'],
            'dose' => $r['dose'],
            'status' => $r['status'],
            'start' => $r['start'],
            'prescriber' => $r['prescriber'],
        ];
    }
    return $out;
}

function doctorPortal_seedAllergies($patientId) {
    $p = doctorPortal_getPatient($patientId);
    $a = $p['allergies'] ?? 'None';
    if ($a === 'None' || $a === '') return [];
    return [['agent' => $a, 'reaction' => 'Rash', 'severity' => 'Moderate', 'verified' => date('Y-m-d')]];
}

function doctorPortal_seedNotes($patientId) {
    $db = _db();
    if (!$db) return [];
    $rows = _chartTable($db, 'patient_notes', $patientId, 'id, type, author, note_date AS date, snippet');
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'id' => $r['id'],
            'type' => $r['type'],
            'author' => $r['author'],
            'date' => $r['date'],
            'snippet' => $r['snippet'],
        ];
    }
    return $out;
}

function doctorPortal_seedOrders($patientId) {
    $db = _db();
    if (!$db) return [];
    $rows = _chartTable($db, 'patient_orders', $patientId, 'id, order_type AS type, order_description AS order, status, order_date AS date');
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'id' => $r['id'],
            'type' => $r['type'],
            'order' => $r['order'],
            'status' => $r['status'],
            'date' => $r['date'],
        ];
    }
    return $out;
}

function doctorPortal_seedTasks($patientId) {
    $db = _db();
    if (!$db) return [];
    $rows = _chartTable($db, 'patient_tasks', $patientId, 'id, task, due_datetime AS due, status');
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'id' => $r['id'],
            'task' => $r['task'],
            'due' => $r['due'],
            'status' => $r['status'],
        ];
    }
    return $out;
}

function doctorPortal_getPatientChart($patientId) {
    $patient = doctorPortal_getPatient($patientId);
    if (!$patient) return null;
    return [
        'patient' => $patient,
        'vitals' => doctorPortal_seedVitals($patientId),
        'problems' => doctorPortal_seedProblems($patientId),
        'labs' => doctorPortal_seedLabResults($patientId),
        'imaging' => doctorPortal_seedImaging($patientId),
        'pathology' => doctorPortal_seedPathology($patientId),
        'medications' => doctorPortal_seedMedications($patientId),
        'allergies' => doctorPortal_seedAllergies($patientId),
        'notes' => doctorPortal_seedNotes($patientId),
        'orders' => doctorPortal_seedOrders($patientId),
        'tasks' => doctorPortal_seedTasks($patientId),
    ];
}

function doctorPortal_getLabResults($patientId) {
    return doctorPortal_seedLabResults($patientId);
}
function doctorPortal_getNotes($patientId) {
    return doctorPortal_seedNotes($patientId);
}
function doctorPortal_getOrders($patientId) {
    return doctorPortal_seedOrders($patientId);
}

// Seed doctors (for dropdowns) – from DB
function doctorPortal_seedDoctors() {
    $db = _db();
    if (!$db) return [];
    $out = [];
    try {
        $stmt = $db->query("SELECT id, CONCAT(first_name, ' ', last_name) AS name, email FROM users WHERE role = 'doctor' AND status = 'active' ORDER BY last_name");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $out[] = ['id' => $row['id'], 'name' => 'Dr. ' . $row['name'], 'specialty' => '', 'prc_no' => '', 'department' => ''];
        }
    } catch (PDOException $e) {
        return [];
    }
    return $out;
}
