<?php

$DB_HOST = "localhost";
$DB_USER = "root";   
$DB_PASS = "";       
$DB_NAME = "nextgenspareslk";

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
  die("DB connection failed: " . $conn->connect_error);
}
