<?php
/**
 * Doctor Portal – mock data layer (Philippine HMS style).
 * Replace with real backend later. Seed data uses Filipino names, Philippine wards, and common local conditions.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/config.php';
}

// ---------- Doctors (Philippine hospital staff) ----------

function doctorPortal_seedDoctors() {
    return [
        ['id' => 1, 'name' => 'Dr. Eduardo Reyes', 'specialty' => 'Internal Medicine', 'prc_no' => 'PRC-123456', 'department' => 'Medicine'],
        ['id' => 2, 'name' => 'Dr. Imelda Cruz', 'specialty' => 'Cardiology', 'prc_no' => 'PRC-123457', 'department' => 'Cardiology'],
        ['id' => 3, 'name' => 'Dr. Roberto Villanueva', 'specialty' => 'General Surgery', 'prc_no' => 'PRC-123458', 'department' => 'Surgery'],
        ['id' => 4, 'name' => 'Dr. Corazon Abad', 'specialty' => 'Obstetrics & Gynecology', 'prc_no' => 'PRC-123459', 'department' => 'OB-Gyne'],
        ['id' => 5, 'name' => 'Dr. Felipe Navarro', 'specialty' => 'Pulmonology', 'prc_no' => 'PRC-123460', 'department' => 'Pulmonology'],
        ['id' => 6, 'name' => 'Dr. Lorna Tan', 'specialty' => 'Pediatrics', 'prc_no' => 'PRC-123461', 'department' => 'Pediatrics'],
    ];
}

// ---------- Seed data generators (Philippine HMS) ----------

function doctorPortal_seedPatients() {
    return [
        ['id' => '1', 'mrn' => 'MRN-2025-001', 'first_name' => 'Maria', 'last_name' => 'Santos', 'dob' => '1975-03-15', 'sex' => 'F', 'ward' => 'Medical Ward 2', 'bed' => '2A', 'allergies' => 'Penicillin', 'code_status' => 'Full Code', 'assigned_doctor_id' => 1, 'philhealth' => '12-345678901-2', 'admission_date' => date('Y-m-d', strtotime('-5 days'))],
        ['id' => '2', 'mrn' => 'MRN-2025-002', 'first_name' => 'Rodrigo', 'last_name' => 'Mendoza', 'dob' => '1982-07-22', 'sex' => 'M', 'ward' => 'Surgical Ward 1', 'bed' => '3B', 'allergies' => 'None', 'code_status' => 'Full Code', 'assigned_doctor_id' => 1, 'philhealth' => '98-765432109-8', 'admission_date' => date('Y-m-d', strtotime('-3 days'))],
        ['id' => '3', 'mrn' => 'MRN-2025-003', 'first_name' => 'Cecilia', 'last_name' => 'Bautista', 'dob' => '1968-11-08', 'sex' => 'F', 'ward' => null, 'bed' => null, 'allergies' => 'Sulfa', 'code_status' => 'Full Code', 'assigned_doctor_id' => 1, 'philhealth' => '11-223344556-7', 'admission_date' => null],
        ['id' => '4', 'mrn' => 'MRN-2025-004', 'first_name' => 'Hector', 'last_name' => 'dela Rosa', 'dob' => '1990-01-30', 'sex' => 'M', 'ward' => 'ICU', 'bed' => '1', 'allergies' => 'None', 'code_status' => 'DNR', 'assigned_doctor_id' => 2, 'philhealth' => '22-334455667-8', 'admission_date' => date('Y-m-d', strtotime('-2 days'))],
        ['id' => '5', 'mrn' => 'MRN-2025-005', 'first_name' => 'Aurora', 'last_name' => 'Santiago', 'dob' => '1955-09-12', 'sex' => 'F', 'ward' => 'Medical Ward 2', 'bed' => '4C', 'allergies' => 'Latex', 'code_status' => 'Full Code', 'assigned_doctor_id' => 1, 'philhealth' => '33-445566778-9', 'admission_date' => date('Y-m-d', strtotime('-7 days'))],
        ['id' => '6', 'mrn' => 'MRN-2025-006', 'first_name' => 'Antonio', 'last_name' => 'Garcia', 'dob' => '1978-05-20', 'sex' => 'M', 'ward' => 'Pay Ward', 'bed' => '101', 'allergies' => 'None', 'code_status' => 'Full Code', 'assigned_doctor_id' => 1, 'philhealth' => '44-556677889-0', 'admission_date' => date('Y-m-d', strtotime('-1 day'))],
        ['id' => '7', 'mrn' => 'MRN-2025-007', 'first_name' => 'Lorna', 'last_name' => 'Ramos', 'dob' => '1985-12-03', 'sex' => 'F', 'ward' => 'Charity Ward', 'bed' => '5A', 'allergies' => 'Iodine', 'code_status' => 'Full Code', 'assigned_doctor_id' => 1, 'philhealth' => null, 'admission_date' => date('Y-m-d', strtotime('-4 days'))],
        ['id' => '8', 'mrn' => 'MRN-2025-008', 'first_name' => 'Emilio', 'last_name' => 'Castillo', 'dob' => '1962-08-14', 'sex' => 'M', 'ward' => null, 'bed' => null, 'allergies' => 'None', 'code_status' => 'Full Code', 'assigned_doctor_id' => 1, 'philhealth' => '55-667788990-1', 'admission_date' => null, 'last_visit' => date('Y-m-d', strtotime('-1 month'))],
    ];
}

function doctorPortal_seedVitals($patientId) {
    $base = [['time' => '06:00', 'bp' => 118, 'hr' => 72, 'temp' => 36.8, 'rr' => 16, 'spo2' => 98],
             ['time' => '10:00', 'bp' => 122, 'hr' => 75, 'temp' => 36.9, 'rr' => 16, 'spo2' => 99],
             ['time' => '14:00', 'bp' => 120, 'hr' => 74, 'temp' => 37.0, 'rr' => 18, 'spo2' => 97],
             ['time' => '18:00', 'bp' => 125, 'hr' => 78, 'temp' => 36.7, 'rr' => 16, 'spo2' => 98]];
    return array_map(function ($r) use ($patientId) {
        $r['patient_id'] = $patientId;
        return $r;
    }, $base);
}

function doctorPortal_seedLabResults($patientId) {
    return [
        ['id' => 1, 'panel' => 'CBC', 'result' => 'WBC 7.2, Hgb 12.4, Hct 37.2, Plt 245', 'status' => 'Final', 'collected' => date('Y-m-d', strtotime('-1 day')) . ' 08:00', 'reported' => date('Y-m-d', strtotime('-1 day')) . ' 09:15'],
        ['id' => 2, 'panel' => 'CMP', 'result' => 'Na 138, K 4.2, Cl 102, CO2 24, Glu 95, Creat 1.0, BUN 14', 'status' => 'Final', 'collected' => date('Y-m-d', strtotime('-1 day')) . ' 08:00', 'reported' => date('Y-m-d', strtotime('-1 day')) . ' 10:00'],
        ['id' => 3, 'panel' => 'Troponin I', 'result' => '0.01 ng/mL', 'status' => 'Final', 'collected' => date('Y-m-d', strtotime('-2 days')) . ' 14:00', 'reported' => date('Y-m-d', strtotime('-2 days')) . ' 15:30'],
        ['id' => 4, 'panel' => 'Blood Culture', 'result' => 'No growth at 48 hrs', 'status' => 'Final', 'collected' => date('Y-m-d', strtotime('-3 days')) . ' 09:00', 'reported' => date('Y-m-d', strtotime('-1 day')) . ' 09:00'],
    ];
}

function doctorPortal_seedImaging($patientId) {
    return [
        ['id' => 1, 'modality' => 'CXR', 'description' => 'Portable chest X-ray', 'result' => 'No focal consolidation. Heart size normal. No pleural effusion.', 'status' => 'Final', 'performed' => date('Y-m-d', strtotime('-1 day')) . ' 07:30'],
        ['id' => 2, 'modality' => 'CT Chest', 'description' => 'CT chest with contrast', 'result' => 'No pulmonary embolism. Small right lower lobe atelectasis.', 'status' => 'Final', 'performed' => date('Y-m-d', strtotime('-2 days')) . ' 16:00'],
    ];
}

function doctorPortal_seedPathology($patientId) {
    return [
        ['id' => 1, 'specimen' => 'Skin biopsy, left arm', 'result' => 'Benign nevus. No malignancy.', 'status' => 'Final', 'signed' => date('Y-m-d', strtotime('-5 days'))],
    ];
}

function doctorPortal_seedProblems($patientId) {
    return [
        ['icd' => 'I10', 'description' => 'Essential hypertension', 'status' => 'Active', 'onset' => date('Y-m-d', strtotime('-1 year'))],
        ['icd' => 'E11.9', 'description' => 'Type 2 diabetes without complications', 'status' => 'Active', 'onset' => date('Y-m-d', strtotime('-2 years'))],
        ['icd' => 'J18.9', 'description' => 'Pneumonia, unspecified', 'status' => 'Resolved', 'onset' => date('Y-m-d', strtotime('-5 days'))],
    ];
}

function doctorPortal_seedMedications($patientId) {
    return [
        ['id' => 1, 'drug' => 'Amlodipine 5 mg', 'dose' => '1 tab PO daily', 'status' => 'Active', 'start' => date('Y-m-d', strtotime('-30 days')), 'prescriber' => 'Dr. Reyes'],
        ['id' => 2, 'drug' => 'Metformin 500 mg', 'dose' => '1 tab PO BID', 'status' => 'Active', 'start' => date('Y-m-d', strtotime('-60 days')), 'prescriber' => 'Dr. Reyes'],
        ['id' => 3, 'drug' => 'Losartan 50 mg', 'dose' => '1 tab PO daily', 'status' => 'Active', 'start' => date('Y-m-d', strtotime('-14 days')), 'prescriber' => 'Dr. Cruz'],
    ];
}

function doctorPortal_seedAllergies($patientId) {
    $p = doctorPortal_getPatient($patientId);
    $a = $p['allergies'] ?? 'None';
    if ($a === 'None') return [];
    return [['agent' => $a, 'reaction' => 'Rash', 'severity' => 'Moderate', 'verified' => date('Y-m-d')]];
}

function doctorPortal_seedNotes($patientId) {
    return [
        ['id' => 1, 'type' => 'Progress', 'author' => 'Dr. Reyes', 'date' => date('Y-m-d H:i', strtotime('-1 day')), 'snippet' => 'Patient stable. Continue current regimen. Plan discharge tomorrow if labs remain stable.'],
        ['id' => 2, 'type' => 'Consult', 'author' => 'Cardiology', 'date' => date('Y-m-d H:i', strtotime('-2 days')), 'snippet' => 'Recommend echo for evaluation of murmur. No acute intervention.'],
        ['id' => 3, 'type' => 'Discharge', 'author' => 'Dr. Reyes', 'date' => date('Y-m-d H:i', strtotime('-5 days')), 'snippet' => 'Discharged on oral antibiotics. Follow-up in clinic in 1 week.'],
    ];
}

function doctorPortal_seedOrders($patientId) {
    return [
        ['id' => 1, 'type' => 'Lab', 'order' => 'CBC, CMP', 'status' => 'Completed', 'date' => date('Y-m-d', strtotime('-1 day'))],
        ['id' => 2, 'type' => 'Medication', 'order' => 'Amlodipine 5 mg PO daily', 'status' => 'Active', 'date' => date('Y-m-d')],
        ['id' => 3, 'type' => 'Imaging', 'order' => 'CXR portable', 'status' => 'Completed', 'date' => date('Y-m-d', strtotime('-1 day'))],
    ];
}

function doctorPortal_seedTasks($patientId) {
    return [
        ['id' => 1, 'task' => 'Review morning labs', 'due' => date('Y-m-d') . ' 09:00', 'status' => 'Done'],
        ['id' => 2, 'task' => 'Discharge summary', 'due' => date('Y-m-d') . ' 12:00', 'status' => 'Pending'],
        ['id' => 3, 'task' => 'Family meeting', 'due' => date('Y-m-d') . ' 14:00', 'status' => 'Pending'],
    ];
}

function doctorPortal_seedAppointments() {
    return [
        ['id' => 1, 'time' => '08:00', 'patient' => 'Maria Santos', 'mrn' => 'MRN-2025-001', 'patient_id' => '1', 'type' => 'Follow-up', 'status' => 'Checked in', 'chief_complaint' => 'Hypertension follow-up'],
        ['id' => 2, 'time' => '08:30', 'patient' => 'Rodrigo Mendoza', 'mrn' => 'MRN-2025-002', 'patient_id' => '2', 'type' => 'Post-op', 'status' => 'Scheduled', 'chief_complaint' => 'Suture removal'],
        ['id' => 3, 'time' => '09:00', 'patient' => 'Cecilia Bautista', 'mrn' => 'MRN-2025-003', 'patient_id' => '3', 'type' => 'Follow-up', 'status' => 'Scheduled', 'chief_complaint' => 'Diabetes monitoring'],
        ['id' => 4, 'time' => '09:30', 'patient' => 'Emilio Castillo', 'mrn' => 'MRN-2025-008', 'patient_id' => '8', 'type' => 'New', 'status' => 'Scheduled', 'chief_complaint' => 'Annual physical'],
        ['id' => 5, 'time' => '10:00', 'patient' => 'Lorna Ramos', 'mrn' => 'MRN-2025-007', 'patient_id' => '7', 'type' => 'Follow-up', 'status' => 'Scheduled', 'chief_complaint' => 'UTI follow-up'],
    ];
}

function doctorPortal_seedSigningQueue() {
    return [
        ['id' => 1, 'type' => 'Lab', 'patient' => 'Maria Santos', 'mrn' => 'MRN-2025-001', 'order' => 'CBC, CMP, HbA1c', 'ordered_at' => date('Y-m-d H:i', strtotime('-2 hours'))],
        ['id' => 2, 'type' => 'Imaging', 'patient' => 'Hector dela Rosa', 'mrn' => 'MRN-2025-004', 'order' => 'CT Chest with contrast', 'ordered_at' => date('Y-m-d H:i', strtotime('-1 hour'))],
        ['id' => 3, 'type' => 'Medication', 'patient' => 'Aurora Santiago', 'mrn' => 'MRN-2025-005', 'order' => 'Losartan 50 mg PO daily', 'ordered_at' => date('Y-m-d H:i', strtotime('-30 min'))],
    ];
}

function doctorPortal_seedConsults() {
    return [
        ['id' => 1, 'patient' => 'Maria Santos', 'mrn' => 'MRN-2025-001', 'patient_id' => '1', 'service' => 'Cardiology', 'requested' => date('Y-m-d', strtotime('-2 days')), 'status' => 'Completed', 'indication' => 'Hypertension, echo for murmur'],
        ['id' => 2, 'patient' => 'Hector dela Rosa', 'mrn' => 'MRN-2025-004', 'patient_id' => '4', 'service' => 'Pulmonology', 'requested' => date('Y-m-d'), 'status' => 'Pending', 'indication' => 'ARDS, vent management'],
        ['id' => 3, 'patient' => 'Aurora Santiago', 'mrn' => 'MRN-2025-005', 'patient_id' => '5', 'service' => 'Nephrology', 'requested' => date('Y-m-d', strtotime('-1 day')), 'status' => 'Completed', 'indication' => 'CKD stage 3, electrolyte review'],
        ['id' => 4, 'patient' => 'Lorna Ramos', 'mrn' => 'MRN-2025-007', 'patient_id' => '7', 'service' => 'Infectious Disease', 'requested' => date('Y-m-d'), 'status' => 'Pending', 'indication' => 'Pyelonephritis, culture review'],
    ];
}

function doctorPortal_seedReferrals() {
    return [
        ['id' => 1, 'from' => 'Dr. Imelda Cruz', 'patient' => 'Maria Santos', 'mrn' => 'MRN-2025-001', 'patient_id' => '1', 'reason' => 'Hypertension follow-up, echo done', 'date' => date('Y-m-d', strtotime('-2 days')), 'status' => 'Accepted'],
        ['id' => 2, 'from' => 'ER', 'patient' => 'Hector dela Rosa', 'mrn' => 'MRN-2025-004', 'patient_id' => '4', 'reason' => 'Chest pain rule-out MI', 'date' => date('Y-m-d'), 'status' => 'Pending'],
        ['id' => 3, 'from' => 'Outpatient Dept', 'patient' => 'Emilio Castillo', 'mrn' => 'MRN-2025-008', 'patient_id' => '8', 'reason' => 'PhilHealth referral – diabetes screening', 'date' => date('Y-m-d', strtotime('-1 day')), 'status' => 'Accepted'],
    ];
}

function doctorPortal_seedPendingResults() {
    return [
        ['id' => 1, 'patient' => 'Rodrigo Mendoza', 'mrn' => 'MRN-2025-002', 'patient_id' => '2', 'order' => 'CXR 2-view', 'ordered' => date('Y-m-d H:i', strtotime('-3 hours')), 'status' => 'In progress'],
        ['id' => 2, 'patient' => 'Maria Santos', 'mrn' => 'MRN-2025-001', 'patient_id' => '1', 'order' => 'HbA1c', 'ordered' => date('Y-m-d H:i', strtotime('-5 hours')), 'status' => 'Specimen received'],
        ['id' => 3, 'patient' => 'Antonio Garcia', 'mrn' => 'MRN-2025-006', 'patient_id' => '6', 'order' => 'Blood culture x2', 'ordered' => date('Y-m-d H:i', strtotime('-2 hours')), 'status' => 'Pending'],
    ];
}

function doctorPortal_seedCriticalAlerts() {
    return [
        ['id' => 1, 'patient' => 'Hector dela Rosa', 'mrn' => 'MRN-2025-004', 'result' => 'K+ 6.2 mEq/L (Critical high)', 'time' => date('Y-m-d H:i', strtotime('-4 hours')), 'acknowledged' => false],
    ];
}

function doctorPortal_seedMessagingThreads() {
    return [
        ['id' => 1, 'from' => 'Nursing – Medical Ward 2', 'subject' => 'Re: Santos, Maria – BP elevated 160/95', 'date' => date('Y-m-d H:i', strtotime('-1 hour')), 'unread' => true],
        ['id' => 2, 'from' => 'Lab', 'subject' => 'Critical: dela Rosa, Hector – Potassium', 'date' => date('Y-m-d H:i', strtotime('-4 hours')), 'unread' => true],
        ['id' => 3, 'from' => 'Dr. Imelda Cruz', 'subject' => 'Cardiology consult note – Santos, Maria', 'date' => date('Y-m-d H:i', strtotime('-1 day')), 'unread' => false],
    ];
}

function doctorPortal_seedAnnouncements() {
    return [
        ['id' => 1, 'title' => 'HIS maintenance – Sunday 02:00–06:00', 'date' => date('Y-m-d', strtotime('-3 days')), 'read' => true, 'department' => 'IT'],
        ['id' => 2, 'title' => 'PhilHealth case rate updates (Feb 2025)', 'date' => date('Y-m-d', strtotime('-1 day')), 'read' => false, 'department' => 'Billing'],
        ['id' => 3, 'title' => 'New dengue NS1 protocol – ER and OPD', 'date' => date('Y-m-d'), 'read' => false, 'department' => 'Medical'],
    ];
}

function doctorPortal_seedORSchedule() {
    return [
        ['id' => 1, 'time' => '07:00', 'end' => '11:00', 'patient' => 'Rodrigo Mendoza', 'mrn' => 'MRN-2025-002', 'procedure' => 'Laparoscopic cholecystectomy', 'room' => 'OR 2', 'surgeon' => 'Dr. Roberto Villanueva', 'status' => 'Scheduled'],
        ['id' => 2, 'time' => '08:00', 'end' => '09:30', 'patient' => 'Carmen Reyes', 'mrn' => 'MRN-2025-009', 'procedure' => 'Cesarean section', 'room' => 'OR 1', 'surgeon' => 'Dr. Corazon Abad', 'status' => 'In progress'],
        ['id' => 3, 'time' => '13:00', 'end' => '15:00', 'patient' => 'Jose Mendoza', 'mrn' => 'MRN-2025-010', 'procedure' => 'Total knee replacement', 'room' => 'OR 2', 'surgeon' => 'Dr. Roberto Villanueva', 'status' => 'Scheduled'],
    ];
}

function doctorPortal_seedOrderSets() {
    return [
        ['id' => 1, 'name' => 'Admission – General Medicine', 'category' => 'Admission', 'updated' => date('Y-m-d', strtotime('-1 month')), 'description' => 'CBC, CMP, CXR, ECG, urinalysis'],
        ['id' => 2, 'name' => 'Dengue protocol (PCMC/DOH)', 'category' => 'Protocol', 'updated' => date('Y-m-d', strtotime('-2 weeks')), 'description' => 'CBC, platelet, Hct, NS1, IgM/IgG'],
        ['id' => 3, 'name' => 'TB DOTS work-up', 'category' => 'Protocol', 'updated' => date('Y-m-d', strtotime('-2 weeks')), 'description' => 'Sputum AFB x3, CXR, LFT'],
        ['id' => 4, 'name' => 'Chest pain rule-out MI', 'category' => 'Protocol', 'updated' => date('Y-m-d', strtotime('-1 week')), 'description' => 'ECG, Troponin I serial, CXR'],
        ['id' => 5, 'name' => 'Post-op day 1 (General Surgery)', 'category' => 'Surgery', 'updated' => date('Y-m-d', strtotime('-1 week')), 'description' => 'CBC, CMP, wound check orders'],
    ];
}

function doctorPortal_seedDischargeList() {
    $patients = doctorPortal_getPatientsForDoctor();
    $out = [];
    foreach ($patients as $p) {
        if (!empty($p['ward']) && in_array($p['ward'], ['Medical Ward 2', 'Surgical Ward 1', 'Pay Ward', 'Charity Ward'])) {
            $out[] = [
                'patient_id' => $p['id'],
                'patient' => $p['last_name'] . ', ' . $p['first_name'],
                'mrn' => $p['mrn'],
                'ward' => $p['ward'],
                'bed' => $p['bed'] ?? '',
                'status' => $p['id'] === '1' ? 'Ready for discharge' : 'Under treatment',
            ];
        }
    }
    if (empty($out)) {
        $p = doctorPortal_getPatient('1');
        if ($p) $out[] = ['patient_id' => '1', 'patient' => 'Santos, Maria', 'mrn' => $p['mrn'], 'ward' => 'Medical Ward 2', 'bed' => '2A', 'status' => 'Ready for discharge'];
    }
    return $out;
}

function doctorPortal_seedProcedureNotes() {
    return [
        ['id' => 1, 'date' => date('Y-m-d', strtotime('-1 week')), 'patient' => 'Rodrigo Mendoza', 'mrn' => 'MRN-2025-002', 'patient_id' => '2', 'procedure' => 'Laparoscopic cholecystectomy – op note on file', 'surgeon' => 'Dr. Roberto Villanueva'],
        ['id' => 2, 'date' => date('Y-m-d', strtotime('-3 days')), 'patient' => 'Antonio Garcia', 'mrn' => 'MRN-2025-006', 'patient_id' => '6', 'procedure' => 'Appendectomy – op note on file', 'surgeon' => 'Dr. Roberto Villanueva'],
    ];
}

function doctorPortal_seedProductivity() {
    return [
        'encounters_this_month' => 124,
        'notes_signed' => 98,
        'rvus_mtd' => 286,
        'outpatient_visits' => 89,
        'inpatient_days' => 35,
    ];
}

function doctorPortal_seedQualityMetrics() {
    return [
        ['metric' => 'Documentation completeness', 'rate' => 94, 'target' => 90],
        ['metric' => 'Medication reconciliation at discharge', 'rate' => 88, 'target' => 85],
        ['metric' => 'Core measures – CAP', 'rate' => 92, 'target' => 90],
        ['metric' => 'Timely discharge summary (24h)', 'rate' => 78, 'target' => 80],
    ];
}

function doctorPortal_seedAuditLog() {
    return [
        ['id' => 1, 'action' => 'Chart access', 'patient_id' => 1, 'reason' => 'Routine rounding', 'at' => date('Y-m-d H:i')],
        ['id' => 2, 'action' => 'Break-glass access', 'patient_id' => 4, 'reason' => 'Emergency consult', 'at' => date('Y-m-d H:i', strtotime('-1 day'))],
    ];
}

// ---------- Repository getters ----------

function doctorPortal_getCurrentDoctorId() {
    return (int)($_SESSION['user_id'] ?? 1);
}

function doctorPortal_getPatientsForDoctor() {
    $doctorId = doctorPortal_getCurrentDoctorId();
    $all = doctorPortal_seedPatients();
    return array_filter($all, function ($p) use ($doctorId) {
        return (int)($p['assigned_doctor_id'] ?? 0) === $doctorId;
    });
}

function doctorPortal_getPatient($patientId) {
    $all = doctorPortal_seedPatients();
    foreach ($all as $p) {
        if ((string)$p['id'] === (string)$patientId) return $p;
    }
    return null;
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

function doctorPortal_canAccessPatient($patientId) {
    $doctorId = doctorPortal_getCurrentDoctorId();
    $p = doctorPortal_getPatient($patientId);
    if (!$p) return false;
    return (int)($p['assigned_doctor_id'] ?? 0) === $doctorId;
}

function doctorPortal_logBreakGlassAccess($patientId, $reason) {
    $_SESSION['doctor_portal_audit'] = $_SESSION['doctor_portal_audit'] ?? [];
    $_SESSION['doctor_portal_audit'][] = [
        'action' => 'Break-glass access',
        'patient_id' => $patientId,
        'reason' => $reason,
        'at' => date('Y-m-d H:i:s'),
    ];
}

function doctorPortal_getAuditLog() {
    $seed = doctorPortal_seedAuditLog();
    $session = $_SESSION['doctor_portal_audit'] ?? [];
    return array_merge($session, $seed);
}
