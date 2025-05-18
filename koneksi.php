<?php
session_start();
$host = "localhost";
$user = "RMAA";
$pass = "PJBLE-SNAP";
$db = "perpustakaan_digital";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("koneksi gagal: " . $conn->connect_error);
}