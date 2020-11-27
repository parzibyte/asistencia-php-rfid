<?php
if (!isset($_GET["employee_id"])) {
    exit("employee_id is required");
}
include_once "functions.php";
$employeeId = $_GET["employee_id"];
setReaderForEmployeePairing($employeeId);
