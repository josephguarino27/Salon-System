<?php
require_once 'db_connect.php';

// ==================== CLIENT MANAGEMENT ====================
function createOrGetClient($conn, $name, $phone) {
    // Check if client exists
    $check = $conn->query("SELECT id FROM clients WHERE phone = '$phone'");
    if ($check->num_rows > 0) return $check->fetch_assoc()['id'];

    // Create new
    $stmt = $conn->prepare("INSERT INTO clients (name, phone) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $phone);
    $stmt->execute();
    return $stmt->insert_id;
}

// ==================== APPOINTMENTS & LOYALTY ====================
function createAppointment($data) {
    global $conn;
    $client_name = $conn->real_escape_string($data['client_name']);
    $client_phone = $conn->real_escape_string($data['client_phone']);
    $stylist_id = (int)$data['stylist_id'];
    $service_id = (int)$data['service_id'];
    $appointment_date = $conn->real_escape_string($data['appointment_date']);
    $appointment_time = $conn->real_escape_string($data['appointment_time']);
    
    // Get Service Details
    $service = $conn->query("SELECT price, duration_minutes FROM services WHERE id = $service_id")->fetch_assoc();
    $amount = $service['price'];
    $duration = $service['duration_minutes'];

    // Get/Create Client
    $client_id = createOrGetClient($conn, $client_name, $client_phone);
    
    $sql = "INSERT INTO appointments (client_id, client_name, client_phone, stylist_id, service_id, duration_minutes, appointment_date, appointment_time, amount_due) 
            VALUES ($client_id, '$client_name', '$client_phone', $stylist_id, $service_id, $duration, '$appointment_date', '$appointment_time', $amount)";
            
    return $conn->query($sql);
}

function updateAppointment($id, $data) {
    global $conn;
    $id = (int)$id;
    $status = $conn->real_escape_string($data['status']);
    // Add other fields as needed
    return $conn->query("UPDATE appointments SET status = '$status' WHERE id = $id");
}

function deleteAppointment($id) {
    global $conn;
    $id = (int)$id;
    return $conn->query("DELETE FROM appointments WHERE id = $id");
}

function completeAppointment($conn, $appt_id) {
    // 1. Mark Complete
    $conn->query("UPDATE appointments SET status = 'completed' WHERE id = $appt_id");

    // 2. Add Loyalty Points (1 point per 100 pesos)
    $sql = "SELECT a.client_id, s.price FROM appointments a 
            JOIN services s ON a.service_id = s.id 
            WHERE a.id = $appt_id";
    $res = $conn->query($sql)->fetch_assoc();
    
    if ($res) {
        $points = floor($res['price'] / 100);
        $client_id = $res['client_id'];
        $conn->query("UPDATE clients SET loyalty_points = loyalty_points + $points WHERE id = $client_id");
    }
}

// ==================== REPORT GENERATION ====================
function getReport($conn, $type, $start, $end) {
    if ($type == 'financial') {
        $sql = "SELECT appointment_date, COUNT(*) as count, SUM(s.price) as revenue 
                FROM appointments a JOIN services s ON a.service_id = s.id 
                WHERE status = 'completed' AND appointment_date BETWEEN '$start' AND '$end' 
                GROUP BY appointment_date";
        $headers = ['Date', 'Appointments', 'Revenue'];
    } elseif ($type == 'staff') {
        $sql = "SELECT st.name, COUNT(*) as count, SUM(s.price) as revenue 
                FROM appointments a 
                JOIN stylists st ON a.stylist_id = st.id
                JOIN services s ON a.service_id = s.id 
                WHERE a.status = 'completed' AND a.appointment_date BETWEEN '$start' AND '$end' 
                GROUP BY st.id";
        $headers = ['Stylist', 'Jobs Done', 'Generated Revenue'];
    }
    return ['headers' => $headers, 'data' => $conn->query($sql)];
}

// ==================== QUEUE & CALENDAR ====================
function getQueue($conn) {
    return $conn->query("SELECT a.*, c.name as client_name, c.loyalty_points, s.service_name 
                         FROM appointments a 
                         JOIN clients c ON a.client_id = c.id 
                         JOIN services s ON a.service_id = s.id 
                         WHERE a.booking_type = 'walk-in' AND a.status = 'pending'");
}
?>