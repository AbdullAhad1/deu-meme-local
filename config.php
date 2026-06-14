<?php
// Database configuration
// Edit these values to match your setup, then use the same database.sql file
$DB_HOST = getenv('DEU_DB_HOST') ?: 'localhost';
$DB_NAME = getenv('DEU_DB_NAME') ?: 'deu_board';
$DB_USER = getenv('DEU_DB_USER') ?: 'root';
$DB_PASS = getenv('DEU_DB_PASS') ?: '';

$conn = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

function db_query($sql) {
    global $conn;
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        die("Query failed: " . mysqli_error($conn));
    }
    return $result;
}

function db_prepare($sql) {
    global $conn;
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        die("Prepare failed: " . mysqli_error($conn));
    }
    return $stmt;
}

function db_escape($value) {
    global $conn;
    return mysqli_real_escape_string($conn, $value);
}

function db_insert_id() {
    global $conn;
    return mysqli_insert_id($conn);
}

function db_affected_rows() {
    global $conn;
    return mysqli_affected_rows($conn);
}
