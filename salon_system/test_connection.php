<?php
echo "<h2>MySQL Connection Diagnostic</h2>";
echo "<div style='background:#f0f0f0; padding:20px;'>";

// Test different connection methods
$configs = [
    ['localhost', 'root', ''],
    ['127.0.0.1', 'root', ''],
    ['localhost:3306', 'root', ''],
    ['127.0.0.1:3306', 'root', '']
];

foreach ($configs as $config) {
    list($host, $user, $pass) = $config;
    
    echo "<h3>Testing: mysqli($host, $user, ***)</h3>";
    
    $start = microtime(true);
    $conn = @new mysqli($host, $user, $pass);
    $time = round((microtime(true) - $start) * 1000, 2);
    
    if ($conn->connect_error) {
        echo "<div style='color:red;'>❌ FAILED after {$time}ms<br>";
        echo "Error: " . $conn->connect_error . "</div>";
    } else {
        echo "<div style='color:green;'>✅ SUCCESS in {$time}ms</div>";
        echo "Server: " . $conn->server_info . "<br>";
        $conn->close();
    }
    echo "<hr>";
}

// Check if MySQL service is running
echo "<h3>Checking MySQL Service Status:</h3>";
if (function_exists('shell_exec')) {
    $output = shell_exec('sc query mysql 2>&1');
    if (strpos($output, 'RUNNING') !== false) {
        echo "<div style='color:green;'>✅ MySQL Service is RUNNING</div>";
    } elseif (strpos($output, 'STOPPED') !== false) {
        echo "<div style='color:orange;'>⚠️ MySQL Service is STOPPED</div>";
    } else {
        echo "<div style='color:red;'>❌ MySQL Service not found or not accessible</div>";
    }
    
    // Check XAMPP MySQL
    echo "<h3>Checking XAMPP MySQL:</h3>";
    $xampp_mysql = shell_exec('tasklist | findstr "mysql"');
    if (strpos($xampp_mysql, 'mysqld') !== false || strpos($xampp_mysql, 'mysql') !== false) {
        echo "<div style='color:green;'>✅ MySQL process found</div>";
        echo "<pre>$xampp_mysql</pre>";
    } else {
        echo "<div style='color:red;'>❌ No MySQL process found</div>";
    }
}

// Check port 3306
echo "<h3>Checking Port 3306:</h3>";
$socket = @fsockopen('127.0.0.1', 3306, $errno, $errstr, 5);
if ($socket) {
    echo "<div style='color:green;'>✅ Port 3306 is open</div>";
    fclose($socket);
} else {
    echo "<div style='color:red;'>❌ Port 3306 is closed or blocked</div>";
    echo "Error: $errstr (Code: $errno)<br>";
}

echo "</div>";
?>