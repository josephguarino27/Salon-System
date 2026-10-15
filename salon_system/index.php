<?php
/**
 * Salon Appointment System - Full Featured Version
 * Features: Revenue Tracking, Stylist Schedules, Customer History, Packages, Loyalty Program
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// ✅ Use the unified connection file
require_once 'db_connect.php';

// Setup tables if they don't exist
setupDatabase($conn);

function setupDatabase($conn) {
    // Stylists table
    $conn->query("CREATE TABLE IF NOT EXISTS stylists (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        specialization VARCHAR(100) NOT NULL,
        commission_rate DECIMAL(5,2) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Services table
    $conn->query("CREATE TABLE IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        service_name VARCHAR(100) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        duration_minutes INT NOT NULL,
        loyalty_points INT DEFAULT 10,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Service Packages table
    $conn->query("CREATE TABLE IF NOT EXISTS service_packages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        package_name VARCHAR(100) NOT NULL,
        description TEXT,
        regular_price DECIMAL(10,2) NOT NULL,
        package_price DECIMAL(10,2) NOT NULL,
        validity_days INT DEFAULT 90,
        loyalty_points INT DEFAULT 50,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Package Items table
    $conn->query("CREATE TABLE IF NOT EXISTS package_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        package_id INT NOT NULL,
        service_id INT NOT NULL,
        quantity INT DEFAULT 1,
        FOREIGN KEY (package_id) REFERENCES service_packages(id) ON DELETE CASCADE,
        FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Clients table
    $conn->query("CREATE TABLE IF NOT EXISTS clients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NOT NULL UNIQUE,
        email VARCHAR(100),
        birthday DATE,
        loyalty_points INT DEFAULT 0,
        total_visits INT DEFAULT 0,
        total_spent DECIMAL(10,2) DEFAULT 0,
        last_visit DATE,
        notes TEXT,
        preferences TEXT,
        allergies TEXT,
        referred_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Client Packages table
    $conn->query("CREATE TABLE IF NOT EXISTS client_packages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        package_id INT NOT NULL,
        purchase_date DATE NOT NULL,
        expiry_date DATE NOT NULL,
        services_remaining TEXT,
        status ENUM('active', 'used', 'expired') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
        FOREIGN KEY (package_id) REFERENCES service_packages(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Appointments table
    $conn->query("CREATE TABLE IF NOT EXISTS appointments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT,
        client_name VARCHAR(100) NOT NULL,
        client_phone VARCHAR(20) NOT NULL,
        client_email VARCHAR(100),
        stylist_id INT NOT NULL,
        service_id INT NOT NULL,
        package_id INT,
        duration_minutes INT NOT NULL DEFAULT 60,
        appointment_date DATE NOT NULL,
        appointment_time TIME NOT NULL,
        booking_type ENUM('scheduled', 'walk-in') DEFAULT 'scheduled',
        status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
        payment_method ENUM('cash', 'card', 'online', 'pending') DEFAULT 'pending',
        payment_status ENUM('paid', 'partial', 'unpaid') DEFAULT 'unpaid',
        amount_paid DECIMAL(10,2) DEFAULT 0,
        amount_due DECIMAL(10,2) DEFAULT 0,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (stylist_id) REFERENCES stylists(id) ON DELETE CASCADE,
        FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Appointment Notes table
    $conn->query("CREATE TABLE IF NOT EXISTS appointment_notes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        appointment_id INT NOT NULL,
        note TEXT NOT NULL,
        created_by VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Stylist Schedules table
    $conn->query("CREATE TABLE IF NOT EXISTS stylist_schedules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        stylist_id INT NOT NULL,
        day_of_week INT NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        is_available BOOLEAN DEFAULT TRUE,
        FOREIGN KEY (stylist_id) REFERENCES stylists(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Stylist Availability (days off)
    $conn->query("CREATE TABLE IF NOT EXISTS stylist_availability (
        id INT AUTO_INCREMENT PRIMARY KEY,
        stylist_id INT NOT NULL,
        date DATE NOT NULL,
        start_time TIME,
        end_time TIME,
        is_available BOOLEAN DEFAULT FALSE,
        reason VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (stylist_id) REFERENCES stylists(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Loyalty Transactions
    $conn->query("CREATE TABLE IF NOT EXISTS loyalty_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        points INT NOT NULL,
        transaction_type ENUM('earned', 'redeemed', 'bonus', 'expired') DEFAULT 'earned',
        description VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Payments table
    $conn->query("CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        appointment_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_method ENUM('cash', 'card', 'online') NOT NULL,
        payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        received_by VARCHAR(100),
        FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Walk-in Queue
    $conn->query("CREATE TABLE IF NOT EXISTS walkin_queue (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_name VARCHAR(100) NOT NULL,
        client_phone VARCHAR(20),
        service_id INT,
        stylist_id INT,
        queue_number INT,
        status ENUM('waiting', 'in_progress', 'completed', 'cancelled') DEFAULT 'waiting',
        estimated_wait_time INT,
        check_in_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        start_time TIMESTAMP NULL,
        end_time TIMESTAMP NULL,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Status History
    $conn->query("CREATE TABLE IF NOT EXISTS status_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        appointment_id INT,
        old_status VARCHAR(50),
        new_status VARCHAR(50) NOT NULL,
        changed_by VARCHAR(100) DEFAULT 'System',
        reason TEXT,
        changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    migrateDatabase($conn);
    insertSampleData($conn);
}

function migrateDatabase($conn) {
    // Columns to add (safe to run multiple times due to IF NOT EXISTS check/logic)
    $columns_to_add = [
        "ALTER TABLE clients ADD COLUMN IF NOT EXISTS birthday DATE AFTER email",
        "ALTER TABLE clients ADD COLUMN IF NOT EXISTS loyalty_points INT DEFAULT 0 AFTER birthday",
        "ALTER TABLE clients ADD COLUMN IF NOT EXISTS total_spent DECIMAL(10,2) DEFAULT 0 AFTER total_visits",
        "ALTER TABLE clients ADD COLUMN IF NOT EXISTS notes TEXT AFTER last_visit",
        "ALTER TABLE clients ADD COLUMN IF NOT EXISTS preferences TEXT AFTER notes",
        "ALTER TABLE clients ADD COLUMN IF NOT EXISTS allergies TEXT AFTER preferences",
        "ALTER TABLE clients ADD COLUMN IF NOT EXISTS referred_by INT AFTER allergies",
        "ALTER TABLE stylists ADD COLUMN IF NOT EXISTS commission_rate DECIMAL(5,2) DEFAULT 0 AFTER specialization",
        "ALTER TABLE services ADD COLUMN IF NOT EXISTS loyalty_points INT DEFAULT 10 AFTER duration_minutes",
        "ALTER TABLE appointments ADD COLUMN IF NOT EXISTS client_id INT AFTER id",
        "ALTER TABLE appointments ADD COLUMN IF NOT EXISTS package_id INT AFTER service_id",
        "ALTER TABLE appointments ADD COLUMN IF NOT EXISTS payment_method ENUM('cash', 'card', 'online', 'pending') DEFAULT 'pending' AFTER status",
        "ALTER TABLE appointments ADD COLUMN IF NOT EXISTS payment_status ENUM('paid', 'partial', 'unpaid') DEFAULT 'unpaid' AFTER payment_method",
        "ALTER TABLE appointments ADD COLUMN IF NOT EXISTS amount_paid DECIMAL(10,2) DEFAULT 0 AFTER payment_status",
        "ALTER TABLE appointments ADD COLUMN IF NOT EXISTS amount_due DECIMAL(10,2) DEFAULT 0 AFTER amount_paid",
        "ALTER TABLE walkin_queue ADD COLUMN IF NOT EXISTS start_time TIMESTAMP NULL AFTER status",
        "ALTER TABLE walkin_queue ADD COLUMN IF NOT EXISTS end_time TIMESTAMP NULL AFTER start_time",
        "ALTER TABLE walkin_queue ADD COLUMN IF NOT EXISTS notes TEXT AFTER end_time"
    ];

    foreach ($columns_to_add as $query) {
        // Suppress errors for duplicate column additions on older MySQL versions that don't support IF NOT EXISTS in ALTER
        try {
            $conn->query($query); 
        } catch (Exception $e) {
            // Ignore duplicate column errors
        }
    }
}

function insertSampleData($conn) {
    // Stylists
    $result = $conn->query("SELECT COUNT(*) as count FROM stylists");
    if ($result && $result->fetch_assoc()['count'] == 0) {
        $conn->query("INSERT INTO stylists (name, specialization, commission_rate) VALUES 
            ('Alex Johnson', 'Hair Coloring & Highlights', 15.00),
            ('Maria Garcia', 'Haircuts & Styling', 20.00),
            ('David Chen', 'Beard Trimming & Grooming', 10.00)");
        $conn->query("INSERT INTO stylist_schedules (stylist_id, day_of_week, start_time, end_time) VALUES 
            (1, 1, '09:00', '18:00'), (1, 2, '09:00', '18:00'), (1, 3, '09:00', '18:00'),
            (1, 4, '09:00', '18:00'), (1, 5, '09:00', '18:00'), (1, 6, '09:00', '18:00'),
            (2, 1, '09:00', '18:00'), (2, 2, '09:00', '18:00'), (2, 3, '09:00', '18:00'),
            (2, 4, '09:00', '18:00'), (2, 5, '09:00', '18:00'), (2, 6, '09:00', '18:00'),
            (3, 1, '09:00', '18:00'), (3, 2, '09:00', '18:00'), (3, 3, '09:00', '18:00'),
            (3, 4, '09:00', '18:00'), (3, 5, '09:00', '18:00'), (3, 6, '09:00', '18:00')");
    }

    // Services & Packages
    $result = $conn->query("SELECT COUNT(*) as count FROM services");
    if ($result && $result->fetch_assoc()['count'] == 0) {
        $conn->query("INSERT INTO services (service_name, price, duration_minutes, loyalty_points) VALUES 
            ('Haircut', 350.00, 30, 35),
            ('Hair Coloring', 1200.00, 120, 120),
            ('Beard Trim', 200.00, 20, 20),
            ('Hair Spa', 800.00, 60, 80),
            ('Hair Treatment', 600.00, 45, 60),
            ('Styling', 400.00, 30, 40)");
        $conn->query("INSERT INTO service_packages (package_name, description, regular_price, package_price, validity_days, loyalty_points) VALUES 
            ('Complete Makeover', 'Haircut + Coloring + Styling', 1950.00, 1650.00, 30, 200),
            ('Monthly Maintenance', '2 Haircuts + 1 Beard Trim', 900.00, 750.00, 60, 100),
            ('Premium Package', 'Haircut + Hair Spa + Treatment', 1750.00, 1400.00, 45, 150)");
        $conn->query("INSERT INTO package_items (package_id, service_id, quantity) VALUES 
            (1, 1, 1), (1, 2, 1), (1, 6, 1),
            (2, 1, 2), (2, 3, 1),
            (3, 1, 1), (3, 4, 1), (3, 5, 1)");
    }
}

// Initialize variables
$message = '';
$message_type = '';
$current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$allowed_pages = ['dashboard', 'appointments', 'queue', 'clients', 'client_detail', 'reports', 'schedules', 'packages', 'loyalty'];
if (!in_array($current_page, $allowed_pages)) {
    $current_page = 'dashboard';
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create Appointment
    if (isset($_POST['create_appointment'])) {
        $client_name = $conn->real_escape_string(trim($_POST['client_name']));
        $client_phone = $conn->real_escape_string(trim($_POST['client_phone']));
        $client_email = isset($_POST['client_email']) ? $conn->real_escape_string(trim($_POST['client_email'])) : '';
        $stylist_id = (int)$_POST['stylist_id'];
        $service_id = (int)$_POST['service_id'];
        $appointment_date = $conn->real_escape_string($_POST['appointment_date']);
        $appointment_time = $conn->real_escape_string($_POST['appointment_time']);
        $service = $conn->query("SELECT price, duration_minutes, loyalty_points FROM services WHERE id = $service_id")->fetch_assoc();
        $duration = $service['duration_minutes'];
        $amount = $service['price'];
        $day_of_week = date('N', strtotime($appointment_date));
        $available = $conn->query("SELECT id FROM stylist_schedules 
            WHERE stylist_id = $stylist_id 
            AND day_of_week = $day_of_week 
            AND start_time <= '$appointment_time' 
            AND end_time >= '$appointment_time'
            AND is_available = TRUE")->num_rows > 0;
        if (!$available) {
            $message = "Stylist is not available at this time";
            $message_type = "error";
        } else {
            $conflict = $conn->query("SELECT id FROM appointments 
                WHERE stylist_id = $stylist_id 
                AND appointment_date = '$appointment_date' 
                AND appointment_time = '$appointment_time'
                AND status != 'cancelled'")->num_rows > 0;
            if ($conflict) {
                $message = "Time slot already booked";
                $message_type = "error";
            } else {
                $client_check = $conn->query("SELECT id FROM clients WHERE phone = '$client_phone'");
                if ($client_check->num_rows > 0) {
                    $client_id = $client_check->fetch_assoc()['id'];
                } else {
                    $conn->query("INSERT INTO clients (name, phone, email) VALUES ('$client_name', '$client_phone', '$client_email')");
                    $client_id = $conn->insert_id;
                }
                $sql = "INSERT INTO appointments (client_id, client_name, client_phone, client_email, stylist_id, service_id, 
                        duration_minutes, appointment_date, appointment_time, amount_due) 
                        VALUES ($client_id, '$client_name', '$client_phone', '$client_email', $stylist_id, $service_id, 
                        $duration, '$appointment_date', '$appointment_time', $amount)";
                if ($conn->query($sql)) {
                    $message = "Appointment created successfully!";
                    $message_type = "success";
                }
            }
        }
    }

    // Process Payment
    if (isset($_POST['process_payment'])) {
        $appointment_id = (int)$_POST['appointment_id'];
        $amount = (float)$_POST['amount'];
        $method = $conn->real_escape_string($_POST['payment_method']);
        $conn->query("INSERT INTO payments (appointment_id, amount, payment_method) VALUES ($appointment_id, $amount, '$method')");
        $total_paid = $conn->query("SELECT SUM(amount) as total FROM payments WHERE appointment_id = $appointment_id")->fetch_assoc()['total'];
        $appointment = $conn->query("SELECT amount_due, client_id, service_id FROM appointments WHERE id = $appointment_id")->fetch_assoc();
        $payment_status = $total_paid >= $appointment['amount_due'] ? 'paid' : 'partial';
        $conn->query("UPDATE appointments SET amount_paid = $total_paid, payment_status = '$payment_status', payment_method = '$method' WHERE id = $appointment_id");
        if ($payment_status == 'paid') {
            $service = $conn->query("SELECT loyalty_points FROM services WHERE id = " . $appointment['service_id'])->fetch_assoc();
            $points = $service['loyalty_points'];
            $conn->query("UPDATE clients SET loyalty_points = loyalty_points + $points, total_spent = total_spent + {$appointment['amount_due']} WHERE id = " . $appointment['client_id']);
            $conn->query("INSERT INTO loyalty_transactions (client_id, points, transaction_type, description) VALUES ({$appointment['client_id']}, $points, 'earned', 'Appointment payment')");
        }
        $message = "Payment processed successfully!";
        $message_type = "success";
    }

    // Update Client Notes
    if (isset($_POST['update_client_notes'])) {
        $client_id = (int)$_POST['client_id'];
        $notes = $conn->real_escape_string($_POST['notes']);
        $preferences = $conn->real_escape_string($_POST['preferences']);
        $allergies = $conn->real_escape_string($_POST['allergies']);
        $birthday = $conn->real_escape_string($_POST['birthday']);
        $conn->query("UPDATE clients SET notes = '$notes', preferences = '$preferences', allergies = '$allergies', birthday = '$birthday' WHERE id = $client_id");
        $message = "Client information updated!";
        $message_type = "success";
    }

    // Add Stylist Schedule
    if (isset($_POST['add_schedule'])) {
        $stylist_id = (int)$_POST['stylist_id'];
        $day = (int)$_POST['day_of_week'];
        $start = $conn->real_escape_string($_POST['start_time']);
        $end = $conn->real_escape_string($_POST['end_time']);
        $conn->query("INSERT INTO stylist_schedules (stylist_id, day_of_week, start_time, end_time) VALUES ($stylist_id, $day, '$start', '$end')");
        $message = "Schedule added successfully!";
        $message_type = "success";
    }

    // Mark Day Off
    if (isset($_POST['mark_day_off'])) {
        $stylist_id = (int)$_POST['stylist_id'];
        $date = $conn->real_escape_string($_POST['date']);
        $reason = $conn->real_escape_string($_POST['reason']);
        $conn->query("INSERT INTO stylist_availability (stylist_id, date, is_available, reason) VALUES ($stylist_id, '$date', FALSE, '$reason')");
        $message = "Day marked as unavailable!";
        $message_type = "success";
    }

    // Purchase Package
    if (isset($_POST['purchase_package'])) {
        $client_id = (int)$_POST['client_id'];
        $package_id = (int)$_POST['package_id'];
        $package = $conn->query("SELECT * FROM service_packages WHERE id = $package_id")->fetch_assoc();
        $expiry = date('Y-m-d', strtotime("+{$package['validity_days']} days"));
        $items = $conn->query("SELECT service_id, quantity FROM package_items WHERE package_id = $package_id");
        $services_arr = [];
        while ($item = $items->fetch_assoc()) {
            $services_arr[$item['service_id']] = $item['quantity'];
        }
        $services_json = json_encode($services_arr);
        $conn->query("INSERT INTO client_packages (client_id, package_id, purchase_date, expiry_date, services_remaining) 
                     VALUES ($client_id, $package_id, CURDATE(), '$expiry', '$services_json')");
        $conn->query("UPDATE clients SET loyalty_points = loyalty_points + {$package['loyalty_points']} WHERE id = $client_id");
        $conn->query("INSERT INTO loyalty_transactions (client_id, points, transaction_type, description) 
                     VALUES ($client_id, {$package['loyalty_points']}, 'earned', 'Package purchase')");
        $message = "Package purchased successfully!";
        $message_type = "success";
    }

    // Add Appointment Note
    if (isset($_POST['add_appointment_note'])) {
        $appointment_id = (int)$_POST['appointment_id'];
        $note = $conn->real_escape_string(trim($_POST['appointment_note']));
        $created_by = $conn->real_escape_string(isset($_POST['created_by']) ? $_POST['created_by'] : 'Staff');
        if (!empty($note)) {
            $conn->query("INSERT INTO appointment_notes (appointment_id, note, created_by) VALUES ($appointment_id, '$note', '$created_by')");
            $message = "Note added successfully!";
            $message_type = "success";
        }
    }

    // Add to Queue
    if (isset($_POST['add_to_queue'])) {
        $client_name = $conn->real_escape_string(trim($_POST['client_name']));
        $client_phone = isset($_POST['client_phone']) ? $conn->real_escape_string(trim($_POST['client_phone'])) : '';
        $service_id = !empty($_POST['service_id']) ? (int)$_POST['service_id'] : 'NULL';
        $stylist_id = !empty($_POST['stylist_id']) ? (int)$_POST['stylist_id'] : 'NULL';
        $queue_result = $conn->query("SELECT COALESCE(MAX(queue_number), 0) + 1 as next_num FROM walkin_queue WHERE DATE(check_in_time) = CURDATE()");
        $queue_number = $queue_result->fetch_assoc()['next_num'];
        $conn->query("INSERT INTO walkin_queue (client_name, client_phone, service_id, stylist_id, queue_number) 
                     VALUES ('$client_name', '$client_phone', $service_id, $stylist_id, $queue_number)");
        $message = "Added to queue! Queue number: #$queue_number";
        $message_type = "success";
    }
}

// Handle status updates
if (isset($_GET['update_status'])) {
    $id = (int)$_GET['update_status'];
    $status = $conn->real_escape_string($_GET['status']);
    $reason = isset($_GET['reason']) ? $conn->real_escape_string($_GET['reason']) : '';
    $old_status_result = $conn->query("SELECT status FROM appointments WHERE id = $id");
    $old_status_row = $old_status_result->fetch_assoc();
    $old_status = $old_status_row['status'];
    if ($conn->query("UPDATE appointments SET status = '$status' WHERE id = $id")) {
        $conn->query("INSERT INTO status_history (appointment_id, old_status, new_status, reason, changed_at) 
                     VALUES ($id, '$old_status', '$status', '$reason', NOW())");
        if ($status == 'completed') {
            $apt = $conn->query("SELECT client_id, service_id FROM appointments WHERE id = $id")->fetch_assoc();
            $conn->query("UPDATE clients SET total_visits = total_visits + 1, last_visit = CURDATE() WHERE id = " . $apt['client_id']);
            $payment = $conn->query("SELECT payment_status FROM appointments WHERE id = $id")->fetch_assoc();
            if ($payment['payment_status'] == 'paid') {
                $service = $conn->query("SELECT loyalty_points FROM services WHERE id = " . $apt['service_id'])->fetch_assoc();
                $points = $service['loyalty_points'];
                $conn->query("UPDATE clients SET loyalty_points = loyalty_points + $points WHERE id = " . $apt['client_id']);
            }
        }
        header("Location: ?page=$current_page");
        exit;
    }
}

if (isset($_POST['update_queue_status'])) {
    $id = (int)$_POST['queue_id'];
    $status = $conn->real_escape_string($_POST['status']);
    $notes = isset($_POST['notes']) ? $conn->real_escape_string($_POST['notes']) : '';
    if ($status == 'in_progress') {
        $conn->query("UPDATE walkin_queue SET status = '$status', start_time = NOW(), notes = '$notes' WHERE id = $id");
    } elseif ($status == 'completed') {
        $conn->query("UPDATE walkin_queue SET status = '$status', end_time = NOW(), notes = '$notes' WHERE id = $id");
    } elseif ($status == 'cancelled') {
        $conn->query("UPDATE walkin_queue SET status = '$status', notes = '$notes' WHERE id = $id");
    }
    $message = "Queue status updated!";
    $message_type = "success";
    header("Location: ?page=queue");
    exit;
}

// Fetch data for forms
$stylists = $conn->query("SELECT * FROM stylists ORDER BY name");
$services = $conn->query("SELECT * FROM services ORDER BY service_name");
$packages = $conn->query("SELECT * FROM service_packages ORDER BY package_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salon Management System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; }
        .container { max-width: 1400px; margin: 20px auto; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px 10px 0 0; }
        .header h1 { text-align: center; margin-bottom: 10px; font-size: 2.5em; }
        .nav { background: #f8f9fa; padding: 15px; border-bottom: 1px solid #ddd; display: flex; gap: 10px; flex-wrap: wrap; }
        .nav a { padding: 10px 20px; background: white; border: 1px solid #ddd; border-radius: 5px; text-decoration: none; color: #333; transition: all 0.3s; }
        .nav a:hover { background: #e9ecef; }
        .nav a.active { background: #667eea; color: white; border-color: #667eea; }
        .content { padding: 30px; }
        .message { padding: 15px; margin-bottom: 20px; border-radius: 5px; animation: slideIn 0.3s; }
        @keyframes slideIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; text-align: center; }
        .stat-card.green { background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%); }
        .stat-card.orange { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-card.blue { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; position: sticky; top: 0; }
        tr:hover { background: #f8f9fa; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin: 20px 0; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { margin-bottom: 5px; font-weight: bold; color: #555; }
        .form-group input, .form-group select, .form-group textarea { padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        .form-group textarea { min-height: 80px; resize: vertical; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; text-decoration: none; display: inline-block; transition: all 0.3s; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
        .btn-primary { background: #667eea; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: #333; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-info { background: #17a2b8; color: white; }
        .btn-sm { padding: 5px 10px; font-size: 14px; }
        .card { background: white; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
        .card h3 { margin-bottom: 15px; color: #667eea; }
        .badge { padding: 5px 10px; border-radius: 15px; font-size: 0.85em; font-weight: bold; display: inline-block; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        .package-card { border: 2px solid #667eea; border-radius: 10px; padding: 20px; margin: 10px 0; }
        .package-card h4 { color: #667eea; margin-bottom: 10px; }
        .package-card .price { font-size: 1.5em; font-weight: bold; color: #28a745; }
        .package-card .old-price { text-decoration: line-through; color: #999; margin-right: 10px; }
        .tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #ddd; }
        .tab { padding: 10px 20px; cursor: pointer; border-bottom: 3px solid transparent; transition: all 0.3s; }
        .tab:hover { background: #f8f9fa; }
        .tab.active { border-bottom-color: #667eea; color: #667eea; font-weight: bold; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        @media (max-width: 768px) {
            .nav a { flex: 1; min-width: 120px; text-align: center; }
            .form-grid { grid-template-columns: 1fr; }
            .stats { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💅✨ Salon Management System</h1>
            <p style="text-align: center;">Complete Business Solution</p>
        </div>
        <div class="nav">
            <a href="?page=dashboard" class="<?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">📊 Dashboard</a>
            <a href="?page=appointments" class="<?php echo $current_page == 'appointments' ? 'active' : ''; ?>">📋 Appointments</a>
            <a href="?page=queue" class="<?php echo $current_page == 'queue' ? 'active' : ''; ?>">👥 Queue</a>
            <a href="?page=clients" class="<?php echo $current_page == 'clients' ? 'active' : ''; ?>">👤 Clients</a>
            <a href="?page=reports" class="<?php echo $current_page == 'reports' ? 'active' : ''; ?>">💰 Reports</a>
            <a href="?page=schedules" class="<?php echo $current_page == 'schedules' ? 'active' : ''; ?>">📅 Schedules</a>
            <a href="?page=packages" class="<?php echo $current_page == 'packages' ? 'active' : ''; ?>">🎁 Packages</a>
            <a href="?page=loyalty" class="<?php echo $current_page == 'loyalty' ? 'active' : ''; ?>">⭐ Loyalty</a>
        </div>
        <div class="content">
            <?php if ($message): ?>
                <div class="message <?php echo htmlspecialchars($message_type); ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($current_page == 'dashboard'): 
                $total_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments")->fetch_assoc()['count'];
                $today_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE appointment_date = CURDATE()")->fetch_assoc()['count'];
                $total_revenue = $conn->query("SELECT SUM(amount_paid) as total FROM appointments WHERE payment_status = 'paid'")->fetch_assoc()['total'] ?? 0;
                $pending_payments = $conn->query("SELECT SUM(amount_due - amount_paid) as total FROM appointments WHERE payment_status != 'paid'")->fetch_assoc()['total'] ?? 0;
            ?>
                <h2>Dashboard Overview</h2>
                <div class="stats">
                    <div class="stat-card">
                        <h3><?php echo $total_appointments; ?></h3>
                        <p>Total Appointments</p>
                    </div>
                    <div class="stat-card green">
                        <h3>₱<?php echo number_format($total_revenue, 2); ?></h3>
                        <p>Total Revenue</p>
                    </div>
                    <div class="stat-card orange">
                        <h3>₱<?php echo number_format($pending_payments, 2); ?></h3>
                        <p>Outstanding Balance</p>
                    </div>
                    <div class="stat-card blue">
                        <h3><?php echo $today_appointments; ?></h3>
                        <p>Today's Appointments</p>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                    <div class="card">
                        <h3>Today's Schedule</h3>
                        <?php
                        $today_apts = $conn->query("SELECT a.*, s.name as stylist_name, sv.service_name 
                            FROM appointments a 
                            LEFT JOIN stylists s ON a.stylist_id = s.id 
                            LEFT JOIN services sv ON a.service_id = sv.id 
                            WHERE a.appointment_date = CURDATE() 
                            ORDER BY a.appointment_time LIMIT 10");
                        if ($today_apts && $today_apts->num_rows > 0): ?>
                            <table>
                                <tr><th>Time</th><th>Client</th><th>Service</th><th>Status</th></tr>
                                <?php while($row = $today_apts->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('h:i A', strtotime($row['appointment_time'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['service_name']); ?></td>
                                    <td><span class="badge badge-<?php echo $row['status'] == 'completed' ? 'success' : 'warning'; ?>"><?php echo $row['status']; ?></span></td>
                                </tr>
                                <?php endwhile; ?>
                            </table>
                        <?php else: ?>
                            <p>No appointments today.</p>
                        <?php endif; ?>
                    </div>
                    <div class="card">
                        <h3>Quick Stats</h3>
                        <?php
                        $month_revenue = $conn->query("SELECT SUM(amount_paid) as total FROM appointments WHERE MONTH(appointment_date) = MONTH(CURDATE()) AND payment_status = 'paid'")->fetch_assoc()['total'] ?? 0;
                        $week_revenue = $conn->query("SELECT SUM(amount_paid) as total FROM appointments WHERE WEEK(appointment_date) = WEEK(CURDATE()) AND payment_status = 'paid'")->fetch_assoc()['total'] ?? 0;
                        $total_clients = $conn->query("SELECT COUNT(*) as count FROM clients")->fetch_assoc()['count'];
                        ?>
                        <p><strong>This Week Revenue:</strong> ₱<?php echo number_format($week_revenue, 2); ?></p>
                        <p><strong>This Month Revenue:</strong> ₱<?php echo number_format($month_revenue, 2); ?></p>
                        <p><strong>Total Clients:</strong> <?php echo $total_clients; ?></p>
                        <div style="margin-top: 20px;">
                            <a href="?page=appointments" class="btn btn-primary">New Appointment</a>
                            <a href="?page=reports" class="btn btn-info">View Reports</a>
                        </div>
                    </div>
                </div>
            <?php elseif ($current_page == 'appointments'): ?>
                <h2>Appointment Management</h2>
                <div class="card">
                    <h3>Create New Appointment</h3>
                    <form method="POST">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Client Name *</label>
                                <input type="text" name="client_name" required>
                            </div>
                            <div class="form-group">
                                <label>Phone *</label>
                                <input type="text" name="client_phone" required>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="client_email">
                            </div>
                            <div class="form-group">
                                <label>Stylist *</label>
                                <select name="stylist_id" required>
                                    <option value="">Select Stylist</option>
                                    <?php $stylists->data_seek(0); while($s = $stylists->fetch_assoc()): ?>
                                        <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Service *</label>
                                <select name="service_id" required>
                                    <option value="">Select Service</option>
                                    <?php $services->data_seek(0); while($sv = $services->fetch_assoc()): ?>
                                        <option value="<?php echo $sv['id']; ?>"><?php echo htmlspecialchars($sv['service_name']); ?> - ₱<?php echo $sv['price']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Date *</label>
                                <input type="date" name="appointment_date" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Time *</label>
                                <input type="time" name="appointment_time" required>
                            </div>
                        </div>
                        <button type="submit" name="create_appointment" class="btn btn-primary">Create Appointment</button>
                    </form>
                </div>
                <div class="card">
                    <h3>All Appointments</h3>
                    <?php
                    $appointments = $conn->query("SELECT a.*, s.name as stylist_name, sv.service_name 
                        FROM appointments a 
                        LEFT JOIN stylists s ON a.stylist_id = s.id 
                        LEFT JOIN services sv ON a.service_id = sv.id 
                        ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT 50");
                    ?>
                    <table>
                        <tr><th>Date</th><th>Time</th><th>Client</th><th>Service</th><th>Stylist</th><th>Amount</th><th>Payment</th><th>Status</th><th>Actions</th></tr>
                        <?php while($row = $appointments->fetch_assoc()): ?>
                        <tr style="<?php echo $row['status'] == 'cancelled' ? 'opacity: 0.6;' : ''; ?>">
                            <td><?php echo date('M d', strtotime($row['appointment_date'])); ?></td>
                            <td><?php echo date('h:i A', strtotime($row['appointment_time'])); ?></td>
                            <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['service_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['stylist_name']); ?></td>
                            <td>₱<?php echo number_format($row['amount_due'], 2); ?></td>
                            <td><span class="badge badge-<?php echo $row['payment_status'] == 'paid' ? 'success' : ($row['payment_status'] == 'partial' ? 'warning' : 'danger'); ?>"><?php echo $row['payment_status']; ?> (₱<?php echo number_format($row['amount_paid'], 2); ?>)</span></td>
                            <td><span class="badge badge-<?php echo $row['status'] == 'completed' ? 'success' : ($row['status'] == 'confirmed' ? 'info' : 'warning'); ?>"><?php echo $row['status']; ?></span></td>
                            <td>
                                <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                    <?php if ($row['status'] == 'pending'): ?>
                                        <a href="#" onclick="openStatusModal(<?php echo $row['id']; ?>, 'confirmed', '<?php echo htmlspecialchars($row['client_name']); ?>')" class="btn-sm btn-success">Confirm</a>
                                    <?php endif; ?>
                                    <?php if ($row['status'] == 'confirmed' && $row['payment_status'] != 'paid'): ?>
                                        <a href="#" onclick="showPayment(<?php echo $row['id']; ?>, <?php echo $row['amount_due']; ?>, <?php echo $row['amount_paid']; ?>)" class="btn-sm btn-primary">Pay</a>
                                    <?php endif; ?>
                                    <?php if ($row['status'] != 'completed' && $row['status'] != 'cancelled'): ?>
                                        <a href="#" onclick="openStatusModal(<?php echo $row['id']; ?>, 'completed', '<?php echo htmlspecialchars($row['client_name']); ?>')" class="btn-sm btn-info">Mark Done</a>
                                    <?php endif; ?>
                                    <?php if ($row['status'] != 'cancelled'): ?>
                                        <a href="#" onclick="openStatusModal(<?php echo $row['id']; ?>, 'cancelled', '<?php echo htmlspecialchars($row['client_name']); ?>')" class="btn-sm btn-danger">Cancel</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </table>
                </div>
                <div id="paymentModal" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); background:white; padding:30px; border-radius:10px; box-shadow:0 0 20px rgba(0,0,0,0.3); z-index:1000;">
                    <h3>Process Payment</h3>
                    <form method="POST">
                        <input type="hidden" name="appointment_id" id="pay_apt_id">
                        <div class="form-group">
                            <label>Amount Due</label>
                            <input type="number" id="amount_due" step="0.01" readonly style="background: #f0f0f0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                        <div class="form-group">
                            <label>Amount Paid Previously</label>
                            <input type="number" id="amount_prev" step="0.01" readonly style="background: #f0f0f0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                        <div class="form-group">
                            <label>Amount to Collect</label>
                            <input type="number" name="amount" id="pay_amount" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>Payment Method</label>
                            <select name="payment_method" required>
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="online">Online Transfer</option>
                                <option value="check">Check</option>
                            </select>
                        </div>
                        <button type="submit" name="process_payment" class="btn btn-success">Process Payment</button>
                        <button type="button" onclick="hidePayment()" class="btn btn-danger">Cancel</button>
                    </form>
                </div>
                <div id="statusModal" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); background:white; padding:30px; border-radius:10px; box-shadow:0 0 20px rgba(0,0,0,0.3); z-index:1000; min-width: 350px;">
                    <h3>Update Appointment Status</h3>
                    <p><strong>Client: </strong><span id="status_client"></span></p>
                    <form method="GET">
                        <input type="hidden" name="page" value="appointments">
                        <input type="hidden" name="update_status" id="status_apt_id">
                        <input type="hidden" name="status" id="status_value">
                        <div class="form-group" id="reasonGroup" style="display: none;">
                            <label>Reason/Notes</label>
                            <textarea name="reason" style="width: 100%; min-height: 80px; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Confirm Update</button>
                        <button type="button" onclick="closeStatusModal()" class="btn btn-danger">Cancel</button>
                    </form>
                </div>
                <script>
                function showPayment(id, due, paid = 0) {
                    document.getElementById('pay_apt_id').value = id;
                    document.getElementById('amount_due').value = due;
                    document.getElementById('amount_prev').value = paid;
                    document.getElementById('pay_amount').value = (due - paid).toFixed(2);
                    document.getElementById('paymentModal').style.display = 'block';
                }
                function hidePayment() {
                    document.getElementById('paymentModal').style.display = 'none';
                }
                function openStatusModal(id, status, clientName) {
                    document.getElementById('status_apt_id').value = id;
                    document.getElementById('status_value').value = status;
                    document.getElementById('status_client').textContent = clientName;
                    if (status === 'cancelled') {
                        document.getElementById('reasonGroup').style.display = 'block';
                    } else {
                        document.getElementById('reasonGroup').style.display = 'none';
                    }
                    document.getElementById('statusModal').style.display = 'block';
                }
                function closeStatusModal() {
                    document.getElementById('statusModal').style.display = 'none';
                }
                window.onclick = function(event) {
                    let paymentModal = document.getElementById('paymentModal');
                    let statusModal = document.getElementById('statusModal');
                    if (event.target == paymentModal) {
                        paymentModal.style.display = 'none';
                    }
                    if (event.target == statusModal) {
                        statusModal.style.display = 'none';
                    }
                }
                </script>
            <?php elseif ($current_page == 'clients'): ?>
                <h2>Client Management</h2>
                <?php
                $clients = $conn->query("SELECT * FROM clients ORDER BY last_visit DESC LIMIT 50");
                ?>
                <table>
                    <tr><th>Name</th><th>Phone</th><th>Email</th><th>Visits</th><th>Spent</th><th>Points</th><th>Last Visit</th><th>Actions</th></tr>
                    <?php while($row = $clients->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['phone']); ?></td>
                        <td><?php echo htmlspecialchars($row['email'] ?? ''); ?></td>
                        <td><?php echo $row['total_visits']; ?></td>
                        <td>₱<?php echo number_format($row['total_spent'], 2); ?></td>
                        <td><span class="badge badge-warning"><?php echo $row['loyalty_points']; ?> pts</span></td>
                        <td><?php echo $row['last_visit'] ? date('M d, Y', strtotime($row['last_visit'])) : 'Never'; ?></td>
                        <td><a href="?page=client_detail&id=<?php echo $row['id']; ?>" class="btn-sm btn-info">View Details</a></td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            <?php elseif ($current_page == 'client_detail' && isset($_GET['id'])): 
                $client_id = (int)$_GET['id'];
                $client = $conn->query("SELECT * FROM clients WHERE id = $client_id")->fetch_assoc();
                $history = $conn->query("SELECT a.*, s.name as stylist_name, sv.service_name 
                    FROM appointments a 
                    LEFT JOIN stylists s ON a.stylist_id = s.id 
                    LEFT JOIN services sv ON a.service_id = sv.id 
                    WHERE a.client_id = $client_id 
                    ORDER BY a.appointment_date DESC");
            ?>
                <h2>Client Details: <?php echo htmlspecialchars($client['name']); ?></h2>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="card">
                        <h3>Client Information</h3>
                        <form method="POST">
                            <input type="hidden" name="client_id" value="<?php echo $client_id; ?>">
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="text" value="<?php echo htmlspecialchars($client['phone']); ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="text" value="<?php echo htmlspecialchars($client['email'] ?? ''); ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label>Birthday</label>
                                <input type="date" name="birthday" value="<?php echo $client['birthday']; ?>">
                            </div>
                            <div class="form-group">
                                <label>Preferences (Hair type, favorite styles, etc.)</label>
                                <textarea name="preferences"><?php echo htmlspecialchars($client['preferences'] ?? ''); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Allergies / Special Requirements</label>
                                <textarea name="allergies"><?php echo htmlspecialchars($client['allergies'] ?? ''); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>General Notes</label>
                                <textarea name="notes"><?php echo htmlspecialchars($client['notes'] ?? ''); ?></textarea>
                            </div>
                            <button type="submit" name="update_client_notes" class="btn btn-primary">Update Information</button>
                        </form>
                    </div>
                    <div>
                        <div class="card">
                            <h3>Statistics</h3>
                            <p><strong>Total Visits:</strong> <?php echo $client['total_visits']; ?></p>
                            <p><strong>Total Spent:</strong> ₱<?php echo number_format($client['total_spent'], 2); ?></p>
                            <p><strong>Loyalty Points:</strong> <?php echo $client['loyalty_points']; ?></p>
                            <p><strong>Last Visit:</strong> <?php echo $client['last_visit'] ? date('M d, Y', strtotime($client['last_visit'])) : 'Never'; ?></p>
                        </div>
                        <div class="card">
                            <h3>Purchase Package</h3>
                            <form method="POST">
                                <input type="hidden" name="client_id" value="<?php echo $client_id; ?>">
                                <div class="form-group">
                                    <label>Select Package</label>
                                    <select name="package_id" required>
                                        <option value="">Choose Package</option>
                                        <?php $packages->data_seek(0); while($pkg = $packages->fetch_assoc()): ?>
                                            <option value="<?php echo $pkg['id']; ?>"><?php echo htmlspecialchars($pkg['package_name']); ?> - ₱<?php echo $pkg['package_price']; ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <button type="submit" name="purchase_package" class="btn btn-success">Purchase Package</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <h3>Appointment History</h3>
                    <?php if ($history && $history->num_rows > 0): ?>
                        <table>
                            <tr><th>Date</th><th>Service</th><th>Stylist</th><th>Amount</th><th>Payment</th><th>Status</th><th>Actions</th></tr>
                            <?php while($row = $history->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($row['appointment_date'])); ?></td>
                                <td><?php echo htmlspecialchars($row['service_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['stylist_name']); ?></td>
                                <td>₱<?php echo number_format($row['amount_due'], 2); ?></td>
                                <td><span class="badge badge-<?php echo $row['payment_status'] == 'paid' ? 'success' : ($row['payment_status'] == 'partial' ? 'warning' : 'danger'); ?>"><?php echo $row['payment_status']; ?></span></td>
                                <td><span class="badge badge-<?php echo $row['status'] == 'completed' ? 'success' : ($row['status'] == 'cancelled' ? 'danger' : 'info'); ?>"><?php echo $row['status']; ?></span></td>
                                <td><a href="#" onclick="openNoteModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['client_name'] ?? ''); ?>')" class="btn-sm btn-info">Notes</a></td>
                            </tr>
                            <?php endwhile; ?>
                        </table>
                    <?php else: ?>
                        <p>No appointment history.</p>
                    <?php endif; ?>
                </div>
                <div id="notesModal" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); background:white; padding:30px; border-radius:10px; box-shadow:0 0 20px rgba(0,0,0,0.3); z-index:1000; min-width: 450px; max-height: 80vh; overflow-y: auto;">
                    <h3>Appointment Notes</h3>
                    <p><strong>Appointment ID: </strong><span id="note_apt_id"></span></p>
                    <div id="existingNotes" style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; max-height: 300px; overflow-y: auto;"></div>
                    <form method="POST">
                        <input type="hidden" name="appointment_id" id="note_appointment_id">
                        <div class="form-group">
                            <label>Add New Note</label>
                            <textarea name="appointment_note" style="width: 100%; min-height: 100px; padding: 10px; border: 1px solid #ddd; border-radius: 5px;" placeholder="What was done, special requests, etc..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>Added By</label>
                            <input type="text" name="created_by" placeholder="Your name/staff ID" value="Staff">
                        </div>
                        <button type="submit" name="add_appointment_note" class="btn btn-success">Add Note</button>
                        <button type="button" onclick="closeNoteModal()" class="btn btn-danger">Close</button>
                    </form>
                </div>
                <script>
                function openNoteModal(id, clientName) {
                    document.getElementById('note_apt_id').value = id;
                    document.getElementById('note_appointment_id').value = id;
                    document.getElementById('notesModal').style.display = 'block';
                    fetch('get_notes.php?apt_id=' + id)
                        .then(response => response.text())
                        .then(data => {
                            document.getElementById('existingNotes').innerHTML = data || '<p style="color: #999;">No notes yet.</p>';
                        });
                }
                function closeNoteModal() {
                    document.getElementById('notesModal').style.display = 'none';
                }
                window.onclick = function(event) {
                    let modal = document.getElementById('notesModal');
                    if (event.target == modal) {
                        modal.style.display = 'none';
                    }
                }
                </script>
            <?php elseif ($current_page == 'reports'): ?>
                <h2>Revenue Reports & Analytics</h2>
                <div class="tabs">
                    <div class="tab active" onclick="showTab('daily')">Daily</div>
                    <div class="tab" onclick="showTab('weekly')">Weekly</div>
                    <div class="tab" onclick="showTab('monthly')">Monthly</div>
                    <div class="tab" onclick="showTab('by-service')">By Service</div>
                    <div class="tab" onclick="showTab('by-stylist')">By Stylist</div>
                </div>
                <div id="daily" class="tab-content active">
                    <div class="card">
                        <h3>Today's Revenue</h3>
                        <?php
                        $today_revenue = $conn->query("SELECT SUM(amount_paid) as total, COUNT(*) as count FROM appointments WHERE appointment_date = CURDATE() AND payment_status = 'paid'")->fetch_assoc();
                        $today_pending = $conn->query("SELECT SUM(amount_due - amount_paid) as total FROM appointments WHERE appointment_date = CURDATE() AND payment_status != 'paid'")->fetch_assoc();
                        ?>
                        <div class="stats">
                            <div class="stat-card green">
                                <h3>₱<?php echo number_format($today_revenue['total'] ?? 0, 2); ?></h3>
                                <p>Paid (<?php echo $today_revenue['count']; ?> appointments)</p>
                            </div>
                            <div class="stat-card orange">
                                <h3>₱<?php echo number_format($today_pending['total'] ?? 0, 2); ?></h3>
                                <p>Pending Payment</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="weekly" class="tab-content">
                    <div class="card">
                        <h3>This Week's Revenue</h3>
                        <?php
                        $week_revenue = $conn->query("SELECT SUM(amount_paid) as total, COUNT(*) as count FROM appointments WHERE WEEK(appointment_date) = WEEK(CURDATE()) AND payment_status = 'paid'")->fetch_assoc();
                        ?>
                        <div class="stats">
                            <div class="stat-card green">
                                <h3>₱<?php echo number_format($week_revenue['total'] ?? 0, 2); ?></h3>
                                <p>Total Revenue (<?php echo $week_revenue['count']; ?> appointments)</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="monthly" class="tab-content">
                    <div class="card">
                        <h3>This Month's Revenue</h3>
                        <?php
                        $month_revenue = $conn->query("SELECT SUM(amount_paid) as total, COUNT(*) as count FROM appointments WHERE MONTH(appointment_date) = MONTH(CURDATE()) AND YEAR(appointment_date) = YEAR(CURDATE()) AND payment_status = 'paid'")->fetch_assoc();
                        ?>
                        <div class="stats">
                            <div class="stat-card green">
                                <h3>₱<?php echo number_format($month_revenue['total'] ?? 0, 2); ?></h3>
                                <p>Total Revenue (<?php echo $month_revenue['count']; ?> appointments)</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="by-service" class="tab-content">
                    <div class="card">
                        <h3>Revenue by Service</h3>
                        <?php
                        $service_revenue = $conn->query("SELECT sv.service_name, COUNT(*) as bookings, SUM(a.amount_paid) as revenue 
                            FROM appointments a 
                            LEFT JOIN services sv ON a.service_id = sv.id 
                            WHERE a.payment_status = 'paid' 
                            GROUP BY a.service_id 
                            ORDER BY revenue DESC");
                        ?>
                        <table>
                            <tr><th>Service</th><th>Bookings</th><th>Revenue</th></tr>
                            <?php while($row = $service_revenue->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['service_name']); ?></td>
                                <td><?php echo $row['bookings']; ?></td>
                                <td>₱<?php echo number_format($row['revenue'], 2); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </table>
                    </div>
                </div>
                <div id="by-stylist" class="tab-content">
                    <div class="card">
                        <h3>Revenue & Commission by Stylist</h3>
                        <?php
                        $stylist_revenue = $conn->query("SELECT s.name, s.commission_rate, COUNT(*) as bookings, SUM(a.amount_paid) as revenue 
                            FROM appointments a 
                            LEFT JOIN stylists s ON a.stylist_id = s.id 
                            WHERE a.payment_status = 'paid' 
                            GROUP BY a.stylist_id 
                            ORDER BY revenue DESC");
                        ?>
                        <table>
                            <tr><th>Stylist</th><th>Bookings</th><th>Revenue</th><th>Commission Rate</th><th>Earnings</th></tr>
                            <?php while($row = $stylist_revenue->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo $row['bookings']; ?></td>
                                <td>₱<?php echo number_format($row['revenue'], 2); ?></td>
                                <td><?php echo $row['commission_rate']; ?>%</td>
                                <td>₱<?php echo number_format($row['revenue'] * $row['commission_rate'] / 100, 2); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </table>
                    </div>
                </div>
                <script>
                function showTab(tab) {
                    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
                    document.querySelectorAll('.tab').forEach(el => el.classList.remove('active'));
                    document.getElementById(tab).classList.add('active');
                    event.target.classList.add('active');
                }
                </script>
            <?php elseif ($current_page == 'schedules'): ?>
                <h2>Stylist Schedules & Availability</h2>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="card">
                        <h3>Set Working Hours</h3>
                        <form method="POST">
                            <div class="form-group">
                                <label>Stylist</label>
                                <select name="stylist_id" required>
                                    <?php $stylists->data_seek(0); while($s = $stylists->fetch_assoc()): ?>
                                        <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Day of Week</label>
                                <select name="day_of_week" required>
                                    <option value="1">Monday</option>
                                    <option value="2">Tuesday</option>
                                    <option value="3">Wednesday</option>
                                    <option value="4">Thursday</option>
                                    <option value="5">Friday</option>
                                    <option value="6">Saturday</option>
                                    <option value="7">Sunday</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Start Time</label>
                                <input type="time" name="start_time" required>
                            </div>
                            <div class="form-group">
                                <label>End Time</label>
                                <input type="time" name="end_time" required>
                            </div>
                            <button type="submit" name="add_schedule" class="btn btn-primary">Add Schedule</button>
                        </form>
                    </div>
                    <div class="card">
                        <h3>Mark Day Off / Break</h3>
                        <form method="POST">
                            <div class="form-group">
                                <label>Stylist</label>
                                <select name="stylist_id" required>
                                    <?php $stylists->data_seek(0); while($s = $stylists->fetch_assoc()): ?>
                                        <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Date</label>
                                <input type="date" name="date" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Reason</label>
                                <input type="text" name="reason" placeholder="e.g., Vacation, Sick Leave">
                            </div>
                            <button type="submit" name="mark_day_off" class="btn btn-warning">Mark Unavailable</button>
                        </form>
                    </div>
                </div>
                <div class="card">
                    <h3>Current Schedules</h3>
                    <?php
                    $schedules = $conn->query("SELECT s.name, ss.day_of_week, ss.start_time, ss.end_time 
                        FROM stylist_schedules ss 
                        LEFT JOIN stylists s ON ss.stylist_id = s.id 
                        ORDER BY s.name, ss.day_of_week");
                    $days = ['', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                    ?>
                    <table>
                        <tr><th>Stylist</th><th>Day</th><th>Working Hours</th></tr>
                        <?php while($row = $schedules->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo $days[$row['day_of_week']]; ?></td>
                            <td><?php echo date('h:i A', strtotime($row['start_time'])); ?> - <?php echo date('h:i A', strtotime($row['end_time'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </table>
                </div>
                <div class="card">
                    <h3>Upcoming Days Off</h3>
                    <?php
                    $days_off = $conn->query("SELECT s.name, sa.date, sa.reason 
                        FROM stylist_availability sa 
                        LEFT JOIN stylists s ON sa.stylist_id = s.id 
                        WHERE sa.date >= CURDATE() AND sa.is_available = FALSE 
                        ORDER BY sa.date");
                    ?>
                    <?php if ($days_off->num_rows > 0): ?>
                        <table>
                            <tr><th>Stylist</th><th>Date</th><th>Reason</th></tr>
                            <?php while($row = $days_off->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                                <td><?php echo htmlspecialchars($row['reason'] ?? 'Not specified'); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </table>
                    <?php else: ?>
                        <p>No scheduled days off.</p>
                    <?php endif; ?>
                </div>
            <?php elseif ($current_page == 'packages'): ?>
                <h2>Service Packages</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <?php
                    $pkgs = $conn->query("SELECT * FROM service_packages ORDER BY package_name");
                    while($pkg = $pkgs->fetch_assoc()):
                        $items = $conn->query("SELECT s.service_name, pi.quantity 
                            FROM package_items pi 
                            LEFT JOIN services s ON pi.service_id = s.id 
                            WHERE pi.package_id = {$pkg['id']}");
                    ?>
                        <div class="package-card">
                            <h4><?php echo htmlspecialchars($pkg['package_name']); ?></h4>
                            <p><?php echo htmlspecialchars($pkg['description']); ?></p>
                            <div style="margin: 15px 0;">
                                <strong>Includes:</strong>
                                <ul style="margin-left: 20px;">
                                    <?php while($item = $items->fetch_assoc()): ?>
                                        <li><?php echo $item['quantity']; ?>x <?php echo htmlspecialchars($item['service_name']); ?></li>
                                    <?php endwhile; ?>
                                </ul>
                            </div>
                            <p>
                                <span class="old-price">₱<?php echo number_format($pkg['regular_price'], 2); ?></span>
                                <span class="price">₱<?php echo number_format($pkg['package_price'], 2); ?></span>
                            </p>
                            <p><small>Valid for <?php echo $pkg['validity_days']; ?> days</small></p>
                            <p><span class="badge badge-warning">+<?php echo $pkg['loyalty_points']; ?> Loyalty Points</span></p>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php elseif ($current_page == 'loyalty'): ?>
                <h2>Loyalty & Rewards Program</h2>
                <div class="card">
                    <h3>Top Loyalty Members</h3>
                    <?php
                    $top_loyalty = $conn->query("SELECT * FROM clients WHERE loyalty_points > 0 ORDER BY loyalty_points DESC LIMIT 20");
                    ?>
                    <table>
                        <tr><th>Rank</th><th>Client</th><th>Points</th><th>Total Spent</th><th>Visits</th></tr>
                        <?php $rank = 1; while($row = $top_loyalty->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo $rank++; ?></strong></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><span class="badge badge-warning"><?php echo $row['loyalty_points']; ?> pts</span></td>
                            <td>₱<?php echo number_format($row['total_spent'], 2); ?></td>
                            <td><?php echo $row['total_visits']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </table>
                </div>
                <div class="card">
                    <h3>Loyalty Program Rules</h3>
                    <ul style="line-height: 2;">
                        <li>✅ Earn points on every service</li>
                        <li>🎁 10th visit gets 20% discount</li>
                        <li>🎂 Birthday month: Extra 50 bonus points</li>
                        <li>👥 Refer a friend: 100 points for both</li>
                        <li>💎 Packages earn extra points</li>
                        <li>⭐ 1000 points = ₱100 discount</li>
                    </ul>
                </div>
            <?php elseif ($current_page == 'queue'): ?>
                <h2>Walk-in Queue Management</h2>
                <div class="card">
                    <h3>Add to Queue</h3>
                    <form method="POST">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Client Name *</label>
                                <input type="text" name="client_name" required>
                            </div>
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="text" name="client_phone">
                            </div>
                            <div class="form-group">
                                <label>Service</label>
                                <select name="service_id">
                                    <option value="">Select Service (Optional)</option>
                                    <?php $services->data_seek(0); while($sv = $services->fetch_assoc()): ?>
                                        <option value="<?php echo $sv['id']; ?>"><?php echo htmlspecialchars($sv['service_name']); ?> - ₱<?php echo $sv['price']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Stylist (Optional)</label>
                                <select name="stylist_id">
                                    <option value="">Any Stylist</option>
                                    <?php $stylists->data_seek(0); while($s = $stylists->fetch_assoc()): ?>
                                        <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <button type="submit" name="add_to_queue" class="btn btn-primary">Add to Queue</button>
                    </form>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                    <div class="stat-card green">
                        <h3><?php echo $conn->query("SELECT COUNT(*) as cnt FROM walkin_queue WHERE status = 'waiting'")->fetch_assoc()['cnt']; ?></h3>
                        <p>Waiting</p>
                    </div>
                    <div class="stat-card orange">
                        <h3><?php echo $conn->query("SELECT COUNT(*) as cnt FROM walkin_queue WHERE status = 'in_progress'")->fetch_assoc()['cnt']; ?></h3>
                        <p>In Progress</p>
                    </div>
                    <div class="stat-card blue">
                        <h3><?php echo $conn->query("SELECT COUNT(*) as cnt FROM walkin_queue WHERE DATE(check_in_time) = CURDATE() AND status = 'completed'")->fetch_assoc()['cnt']; ?></h3>
                        <p>Completed Today</p>
                    </div>
                </div>
                <div class="card">
                    <h3>In Progress</h3>
                    <?php
                    $in_progress = $conn->query("SELECT w.*, sv.service_name, s.name as stylist_name FROM walkin_queue w 
                        LEFT JOIN services sv ON w.service_id = sv.id 
                        LEFT JOIN stylists s ON w.stylist_id = s.id 
                        WHERE w.status = 'in_progress' 
                        ORDER BY w.check_in_time DESC");
                    ?>
                    <?php if ($in_progress->num_rows > 0): ?>
                        <?php while($row = $in_progress->fetch_assoc()): ?>
                        <div style="background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 8px; border-left: 4px solid #ffc107;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <h4 style="margin: 0; color: #856404;">🔄 <?php echo htmlspecialchars($row['client_name']); ?></h4>
                                    <p style="margin: 5px 0; color: #856404; font-size: 0.9em;">
                                        Queue #<?php echo $row['queue_number']; ?> 
                                        <?php if ($row['service_name']): ?>
                                            | <?php echo htmlspecialchars($row['service_name']); ?>
                                        <?php endif; ?>
                                        <?php if ($row['stylist_name']): ?>
                                            | With <?php echo htmlspecialchars($row['stylist_name']); ?>
                                        <?php endif; ?>
                                    </p>
                                    <p style="margin: 5px 0; color: #856404; font-size: 0.85em;">Started: <?php echo date('h:i A', strtotime($row['start_time'])); ?></p>
                                </div>
                                <div style="display: flex; gap: 5px;">
                                    <button onclick="openQueueStatusModal(<?php echo $row['id']; ?>, 'completed', '<?php echo htmlspecialchars($row['client_name']); ?>')" class="btn btn-success btn-sm">✓ Done</button>
                                    <button onclick="openQueueStatusModal(<?php echo $row['id']; ?>, 'cancelled', '<?php echo htmlspecialchars($row['client_name']); ?>')" class="btn btn-danger btn-sm">✕ Cancel</button>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="color: #999; padding: 20px; text-align: center;">No one in progress.</p>
                    <?php endif; ?>
                </div>
                <div class="card">
                    <h3>Waiting Queue</h3>
                    <?php
                    $queue = $conn->query("SELECT w.*, sv.service_name, s.name as stylist_name FROM walkin_queue w 
                        LEFT JOIN services sv ON w.service_id = sv.id 
                        LEFT JOIN stylists s ON w.stylist_id = s.id 
                        WHERE w.status = 'waiting' 
                        ORDER BY w.queue_number");
                    ?>
                    <?php if ($queue->num_rows > 0): ?>
                        <?php while($row = $queue->fetch_assoc()): ?>
                        <div style="background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; border-left: 4px solid #667eea;">
                            <div style="flex: 1;">
                                <h3 style="color: #667eea; margin: 0; font-size: 1.5em;">Queue #<?php echo $row['queue_number']; ?></h3>
                                <p style="margin: 5px 0; font-weight: bold;"><?php echo htmlspecialchars($row['client_name']); ?></p>
                                <?php if ($row['phone']): ?>
                                    <p style="margin: 2px 0; color: #666; font-size: 0.9em;">📱 <?php echo htmlspecialchars($row['phone']); ?></p>
                                <?php endif; ?>
                                <p style="margin: 2px 0; color: #666; font-size: 0.9em;">
                                    <?php if ($row['service_name']): ?>
                                        Service: <?php echo htmlspecialchars($row['service_name']); ?>
                                    <?php endif; ?>
                                    <?php if ($row['stylist_name']): ?>
                                        | <?php echo htmlspecialchars($row['stylist_name']); ?>
                                    <?php endif; ?>
                                </p>
                                <p style="margin: 2px 0; color: #999; font-size: 0.85em;">Checked in: <?php echo date('h:i A', strtotime($row['check_in_time'])); ?></p>
                            </div>
                            <div style="display: flex; gap: 5px; flex-wrap: wrap; justify-content: flex-end;">
                                <button onclick="openQueueStatusModal(<?php echo $row['id']; ?>, 'in_progress', '<?php echo htmlspecialchars($row['client_name']); ?>')" class="btn btn-warning btn-sm">▶ Start</button>
                                <button onclick="openQueueStatusModal(<?php echo $row['id']; ?>, 'completed', '<?php echo htmlspecialchars($row['client_name']); ?>')" class="btn btn-success btn-sm">✓ Done</button>
                                <button onclick="openQueueStatusModal(<?php echo $row['id']; ?>, 'cancelled', '<?php echo htmlspecialchars($row['client_name']); ?>')" class="btn btn-danger btn-sm">✕ Cancel</button>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="color: #999; padding: 20px; text-align: center;">Queue is empty!</p>
                    <?php endif; ?>
                </div>
                <div id="queueStatusModal" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); background:white; padding:30px; border-radius:10px; box-shadow:0 0 20px rgba(0,0,0,0.3); z-index:1000; min-width: 350px;">
                    <h3>Update Queue Status</h3>
                    <p><strong>Client: </strong><span id="queue_client"></span></p>
                    <form method="POST">
                        <input type="hidden" name="queue_id" id="queue_id">
                        <input type="hidden" name="status" id="queue_status_value">
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" style="width: 100%; min-height: 80px; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"></textarea>
                        </div>
                        <button type="submit" name="update_queue_status" class="btn btn-primary">Confirm</button>
                        <button type="button" onclick="closeQueueStatusModal()" class="btn btn-danger">Cancel</button>
                    </form>
                </div>
                <script>
                function openQueueStatusModal(id, status, clientName) {
                    document.getElementById('queue_id').value = id;
                    document.getElementById('queue_status_value').value = status;
                    document.getElementById('queue_client').textContent = clientName;
                    document.getElementById('queueStatusModal').style.display = 'block';
                }
                function closeQueueStatusModal() {
                    document.getElementById('queueStatusModal').style.display = 'none';
                }
                window.onclick = function(event) {
                    let queueStatusModal = document.getElementById('queueStatusModal');
                    if (event.target == queueStatusModal) {
                        queueStatusModal.style.display = 'none';
                    }
                }
                </script>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>