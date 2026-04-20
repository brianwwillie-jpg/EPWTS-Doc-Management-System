<?php
function generateDocID($prefix, $fileNumber) {
    $year = date('y');
    $day = date('d');
    $month = date('m');
    // Prefix (e.g., AB) + Year + Day + Month + Padded File Number
    return $prefix . $year . $day . $month . str_pad($fileNumber, 3, '0', STR_PAD_LEFT);
}

/**
 * Returns a Bootstrap badge with the specific EPWTS color codes
 * Green = Ready, Red = Declined, Yellow = Pending, Black = Default
 */
function getStatusBadge($status) {
    switch($status) {
        case 'Approved': 
            return '<span class="badge status-ready">Ready (Green)</span>';
        case 'Declined': 
            return '<span class="badge status-declined">Declined (Red)</span>';
        case 'Pending': 
            return '<span class="badge status-pending">Pending (Yellow)</span>';
        default: 
            return '<span class="badge status-request">Send Request (Black)</span>';
    }
}
?>
