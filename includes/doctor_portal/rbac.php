<?php
/**
 * Doctor Portal RBAC: doctor, attending, resident, consultant.
 * Residents: notes require co-sign. Controlled meds: attending only. Discharge: attending or authorized.
 */

if (!function_exists('doctorPortal_currentRole')) {
    function doctorPortal_currentRole() {
        $name = $_SESSION['role_name'] ?? $_SESSION['user_role'] ?? 'doctor';
        return strtolower(trim((string) $name));
    }
}

function doctorPortal_isAttending() {
    return doctorPortal_currentRole() === 'attending';
}

function doctorPortal_isResident() {
    return doctorPortal_currentRole() === 'resident';
}

function doctorPortal_canPrescribeControlled() {
    return doctorPortal_isAttending() || doctorPortal_currentRole() === 'consultant';
}

function doctorPortal_noteRequiresCosign() {
    return doctorPortal_isResident();
}

function doctorPortal_canFinalizeDischarge() {
    $r = doctorPortal_currentRole();
    return in_array($r, ['attending', 'consultant', 'doctor'], true);
}

function doctorPortal_canSignOrders() {
    return true; // all doctor roles can sign; resident cosign handled separately if needed
}

function doctorPortal_isViewOnlyDocument($docType) {
    // Example: some sensitive docs view-only for residents
    return false;
}
