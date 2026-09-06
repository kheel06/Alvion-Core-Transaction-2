<?php
/**
 * Demo/sample data for report pages when database tables are missing or empty.
 * Ensures reports and export always have data to display.
 */

if (!function_exists('getDemoBillingData')) {
    function getDemoBillingData($start_date, $end_date) {
        $bills = [];
        $patients = [
            ['first_name' => 'Maria', 'last_name' => 'Santos', 'hospital_id' => 'H-001'],
            ['first_name' => 'Juan', 'last_name' => 'Dela Cruz', 'hospital_id' => 'H-002'],
            ['first_name' => 'Rosa', 'last_name' => 'Reyes', 'hospital_id' => 'H-003'],
            ['first_name' => 'Pedro', 'last_name' => 'Bautista', 'hospital_id' => 'H-004'],
            ['first_name' => 'Ana', 'last_name' => 'Gomez', 'hospital_id' => 'H-005'],
        ];
        $statuses = ['paid', 'paid', 'pending', 'partial', 'overdue'];
        $methods = ['cash', 'insurance', 'cash', 'credit', 'cash'];
        for ($i = 1; $i <= 8; $i++) {
            $p = $patients[($i - 1) % count($patients)];
            $total = rand(2000, 15000);
            $paid = $statuses[($i - 1) % 5] === 'paid' ? $total : ($statuses[($i - 1) % 5] === 'partial' ? round($total * 0.5) : 0);
            $bills[] = [
                'bill_number' => 'INV-2026-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'bill_date' => date('Y-m-d', strtotime($start_date . ' +' . (($i - 1) % 5) . ' days')),
                'first_name' => $p['first_name'],
                'last_name' => $p['last_name'],
                'hospital_id' => $p['hospital_id'],
                'total_amount' => $total,
                'paid_amount' => $paid,
                'balance_amount' => $total - $paid,
                'payment_status' => $statuses[($i - 1) % 5],
                'payment_method' => $methods[($i - 1) % 5],
                'created_by_fname' => 'Admin',
                'created_by_lname' => 'User',
            ];
        }
        return $bills;
    }
}

if (!function_exists('getDemoBillingStats')) {
    function getDemoBillingStats($bills) {
        $total_billed = array_sum(array_column($bills, 'total_amount'));
        $total_paid = array_sum(array_column($bills, 'paid_amount'));
        $total_balance = array_sum(array_column($bills, 'balance_amount'));
        return [
            'total_bills' => count($bills),
            'total_billed' => $total_billed,
            'total_paid' => $total_paid,
            'total_balance' => $total_balance,
            'paid_amount_total' => $total_paid,
            'pending_amount_total' => $total_balance,
            'partial_amount_total' => 0,
            'overdue_amount_total' => 0,
        ];
    }
}

if (!function_exists('getDemoERTriageData')) {
    function getDemoERTriageData($start_date, $end_date) {
        $cases = [];
        $patients = [
            ['first_name' => 'Carlos', 'last_name' => 'Lim', 'hospital_id' => 'H-010', 'age' => 45, 'gender' => 'M'],
            ['first_name' => 'Elena', 'last_name' => 'Vasquez', 'hospital_id' => 'H-011', 'age' => 32, 'gender' => 'F'],
            ['first_name' => 'Roberto', 'last_name' => 'Santiago', 'hospital_id' => 'H-012', 'age' => 58, 'gender' => 'M'],
            ['first_name' => 'Lorna', 'last_name' => 'Dimaguiba', 'hospital_id' => 'H-013', 'age' => 28, 'gender' => 'F'],
            ['first_name' => 'Miguel', 'last_name' => 'Torres', 'hospital_id' => 'H-014', 'age' => 5, 'gender' => 'M'],
        ];
        $levels = ['resuscitation', 'emergency', 'urgent', 'semi_urgent', 'non_urgent'];
        $statuses = ['discharged', 'admitted', 'in_progress', 'waiting', 'discharged'];
        $complaints = ['Chest pain', 'Laceration', 'Fever', 'Head injury', 'Abdominal pain'];
        for ($i = 1; $i <= 10; $i++) {
            $p = $patients[($i - 1) % count($patients)];
            $cases[] = [
                'created_at' => date('Y-m-d H:i:s', strtotime($start_date . ' +' . (($i - 1) % 7) . ' days') + ($i * 3600)),
                'first_name' => $p['first_name'],
                'last_name' => $p['last_name'],
                'hospital_id' => $p['hospital_id'],
                'age' => $p['age'],
                'gender' => $p['gender'],
                'chief_complaint' => $complaints[($i - 1) % 5],
                'triage_level' => $levels[($i - 1) % 5],
                'priority_score' => rand(1, 5),
                'status' => $statuses[($i - 1) % 5],
                'nurse_fname' => 'Nurse',
                'nurse_lname' => 'Staff',
            ];
        }
        return $cases;
    }
}

if (!function_exists('getDemoERStats')) {
    function getDemoERStats($er_cases) {
        $stats = [
            'total_cases' => count($er_cases),
            'resuscitation' => 0, 'emergency' => 0, 'urgent' => 0, 'semi_urgent' => 0, 'non_urgent' => 0,
            'waiting' => 0, 'in_progress' => 0, 'admitted' => 0, 'discharged' => 0, 'transferred' => 0
        ];
        foreach ($er_cases as $c) {
            if (!empty($c['triage_level']) && isset($stats[$c['triage_level']])) $stats[$c['triage_level']]++;
            if (!empty($c['status']) && isset($stats[$c['status']])) $stats[$c['status']]++;
        }
        return $stats;
    }
}

if (!function_exists('getDemoBedOccupancyData')) {
    function getDemoBedOccupancyData() {
        $ward_stats = [
            ['ward_name' => 'Medical Ward', 'ward_code' => 'MED-01', 'capacity' => 30, 'total_beds' => 30, 'occupied_beds' => 24, 'available_beds' => 5, 'maintenance_beds' => 1, 'occupancy_rate' => 80.00],
            ['ward_name' => 'Surgical Ward', 'ward_code' => 'SUR-01', 'capacity' => 25, 'total_beds' => 25, 'occupied_beds' => 20, 'available_beds' => 4, 'maintenance_beds' => 1, 'occupancy_rate' => 80.00],
            ['ward_name' => 'Pediatric Ward', 'ward_code' => 'PED-01', 'capacity' => 20, 'total_beds' => 20, 'occupied_beds' => 14, 'available_beds' => 6, 'maintenance_beds' => 0, 'occupancy_rate' => 70.00],
            ['ward_name' => 'ICU', 'ward_code' => 'ICU-01', 'capacity' => 10, 'total_beds' => 10, 'occupied_beds' => 8, 'available_beds' => 2, 'maintenance_beds' => 0, 'occupancy_rate' => 80.00],
        ];
        $current_occupancy = [
            'total_beds' => 85,
            'occupied_beds' => 66,
            'available_beds' => 17,
            'maintenance_beds' => 2,
            'overall_occupancy_rate' => 77.65,
        ];
        return ['ward_stats' => $ward_stats, 'current_occupancy' => $current_occupancy];
    }
}

if (!function_exists('getDemoAppointmentsData')) {
    function getDemoAppointmentsData($start_date, $end_date) {
        $appointments = [];
        $patients = [
            ['patient_fname' => 'Maria', 'patient_lname' => 'Santos', 'hospital_id' => 'H-001'],
            ['patient_fname' => 'Juan', 'patient_lname' => 'Dela Cruz', 'hospital_id' => 'H-002'],
            ['patient_fname' => 'Rosa', 'patient_lname' => 'Reyes', 'hospital_id' => 'H-003'],
        ];
        for ($i = 1; $i <= 12; $i++) {
            $p = $patients[($i - 1) % count($patients)];
            $appointments[] = [
                'id' => $i,
                'appointment_date' => date('Y-m-d', strtotime($start_date . ' +' . (($i - 1) % 10) . ' days')),
                'appointment_time' => date('H:i:s', strtotime('08:00') + ($i * 3600)),
                'appointment_number' => 'APT-2026-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'patient_fname' => $p['patient_fname'],
                'patient_lname' => $p['patient_lname'],
                'hospital_id' => $p['hospital_id'],
                'doctor_fname' => 'Maria',
                'doctor_lname' => 'Santos',
                'specialization' => 'Internal Medicine',
                'room_name' => 'Room ' . (($i % 3) + 1),
                'appointment_type' => $i % 2 ? 'consultation' : 'follow_up',
                'status' => $i % 3 === 0 ? 'completed' : ($i % 3 === 1 ? 'scheduled' : 'confirmed'),
                'is_walkin' => $i % 4 === 0 ? 1 : 0,
            ];
        }
        return $appointments;
    }
}

if (!function_exists('canExportReport')) {
    function canExportReport() {
        $role = strtolower(trim($_SESSION['role_name'] ?? $_SESSION['user_role'] ?? ''));
        return in_array($role, ['admin', 'super_admin', 'super admin'], true);
    }
}
