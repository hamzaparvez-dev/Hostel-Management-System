<?php
// Oracle Database Connection Configuration
// For Nav Purush Boys Hostel Management System

// Oracle connection parameters
$host = "localhost";  // Oracle server hostname
$port = "1521";       // Oracle default port
$service_name = "XE"; // Oracle service name (XE for Express Edition)
$username = "hostel_admin";  // Oracle username
$password = "hostel123";     // Oracle password

// Oracle connection string
$tns = "(DESCRIPTION =
    (ADDRESS = (PROTOCOL = TCP)(HOST = $host)(PORT = $port))
    (CONNECT_DATA =
        (SERVER = DEDICATED)
        (SERVICE_NAME = $service_name)
    )
)";

try {
    // Create Oracle connection using OCI8
    $conn = oci_connect($username, $password, $tns, 'AL32UTF8');
    
    if (!$conn) {
        $e = oci_error();
        trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
    }
    
    // Set session parameters for better performance
    oci_execute(oci_parse($conn, "ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD'"));
    oci_execute(oci_parse($conn, "ALTER SESSION SET NLS_TIMESTAMP_FORMAT = 'YYYY-MM-DD HH24:MI:SS'"));
    
} catch (Exception $e) {
    die("Connection failed: " . $e->getMessage());
}

// Helper function to execute queries with error handling
function executeQuery($conn, $sql, $params = array()) {
    $stmt = oci_parse($conn, $sql);
    
    if (!$stmt) {
        $e = oci_error($conn);
        trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
    }
    
    // Bind parameters if provided
    if (!empty($params)) {
        foreach ($params as $key => $value) {
            oci_bind_by_name($stmt, $key, $params[$key]);
        }
    }
    
    $result = oci_execute($stmt);
    
    if (!$result) {
        $e = oci_error($stmt);
        trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
    }
    
    return $stmt;
}

// Helper function to fetch all rows
function fetchAll($stmt) {
    $rows = array();
    while ($row = oci_fetch_assoc($stmt)) {
        $rows[] = $row;
    }
    return $rows;
}

// Helper function to fetch single row
function fetchRow($stmt) {
    return oci_fetch_assoc($stmt);
}

// Helper function to get row count
function getRowCount($stmt) {
    return oci_num_rows($stmt);
}

// Helper function to get last insert ID (for Oracle sequences)
function getLastInsertId($conn, $sequence_name) {
    $sql = "SELECT $sequence_name.CURRVAL FROM DUAL";
    $stmt = executeQuery($conn, $sql);
    $row = fetchRow($stmt);
    return $row['CURRVAL'];
}

// Helper function to escape strings for Oracle
function escapeString($conn, $string) {
    return str_replace("'", "''", $string);
}

// Helper function to format date for Oracle
function formatDateForOracle($date) {
    return date('Y-m-d', strtotime($date));
}

// Helper function to format timestamp for Oracle
function formatTimestampForOracle($timestamp) {
    return date('Y-m-d H:i:s', strtotime($timestamp));
}
?>