<?php
require_once 'db_connect.php';

$apt_id = isset($_GET['apt_id']) ? (int)$_GET['apt_id'] : 0;

if ($apt_id > 0) {
    $notes = $conn->query("SELECT * FROM appointment_notes WHERE appointment_id = $apt_id ORDER BY created_at DESC");
    
    if ($notes && $notes->num_rows > 0) {
        $output = '<h4 style="margin-top: 0;">Previous Notes:</h4>';
        while($note = $notes->fetch_assoc()) {
            $output .= '<div style="background: white; padding: 10px; margin: 8px 0; border-left: 3px solid #667eea; border-radius: 3px;">';
            $output .= '<p style="margin: 0; font-weight: bold; color: #667eea;">' . htmlspecialchars($note['created_by']) . '</p>';
            $output .= '<p style="margin: 5px 0; color: #333;">' . htmlspecialchars($note['note']) . '</p>';
            $output .= '<p style="margin: 0; font-size: 0.85em; color: #999;">' . date('M d, Y h:i A', strtotime($note['created_at'])) . '</p>';
            $output .= '</div>';
        }
        echo $output;
    }
}
$conn->close();
?>
