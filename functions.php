<?php
/*

  ____          _____               _ _           _       
 |  _ \        |  __ \             (_) |         | |      
 | |_) |_   _  | |__) |_ _ _ __ _____| |__  _   _| |_ ___ 
 |  _ <| | | | |  ___/ _` | '__|_  / | '_ \| | | | __/ _ \
 | |_) | |_| | | |  | (_| | |   / /| | |_) | |_| | ||  __/
 |____/ \__, | |_|   \__,_|_|  /___|_|_.__/ \__, |\__\___|
         __/ |                               __/ |        
        |___/                               |___/         
    
____________________________________
/ Si necesitas ayuda, contáctame en \
\ https://parzibyte.me               /
 ------------------------------------
        \   ^__^
         \  (oo)\_______
            (__)\       )\/\
                ||----w |
                ||     ||
Creado por Parzibyte (https://parzibyte.me).
------------------------------------------------------------------------------------------------
Si el código es útil para ti, puedes agradecerme siguiéndome: https://parzibyte.me/blog/sigueme/
Y compartiendo mi blog con tus amigos
También tengo canal de YouTube: https://www.youtube.com/channel/UCroP4BTWjfM0CkGB6AFUoBg?sub_confirmation=1
------------------------------------------------------------------------------------------------
*/ ?>
<?php
if (!defined("RFID_STATUS_FILE")) {
    define("RFID_STATUS_FILE", "rfid_status");
}
if (!defined("RFID_STATUS_READING")) {
    define("RFID_STATUS_READING", "r");
}
if (!defined("RFID_STATUS_PAIRING")) {
    define("RFID_STATUS_PAIRING", "p");
}
if (!defined("PAIRING_EMPLOYEE_ID_FILE")) {
    define("PAIRING_EMPLOYEE_ID_FILE", "pairing_employee_id_file");
}

function getEmployeesWithRfid()
{
    $query = "SELECT employee_id, rfid_serial FROM employee_rfid";
    $db = getDatabase();
    $statement = $db->query($query);
    return $statement->fetchAll();
}

function onRfidSerialRead($rfidSerial)
{
    if (getReaderStatus() === RFID_STATUS_PAIRING) {
        pairEmployeeWithRfid($rfidSerial, getPairingEmployeeId());
        setReaderStatus(RFID_STATUS_READING);
    } else {
        $employee = getEmployeeByRfidSerial($rfidSerial);
        if ($employee) {
            saveEmployeeAttendance($employee->id);
        }
    }
}
function saveEmployeeAttendance($employeeId)
{

    $date = date("Y-m-d");
    $status = "presence";
    $query = "INSERT INTO employee_attendance(employee_id, date, status) VALUES (?, ?, ?)";
    $db = getDatabase();
    $statement = $db->prepare($query);
    return $statement->execute([$employeeId, $date, $status]);
}

function setReaderForEmployeePairing($employeeId)
{
    setReaderStatus(RFID_STATUS_PAIRING);
    setPairingEmployeeId($employeeId);
}

function setPairingEmployeeId($employeeId)
{
    file_put_contents(PAIRING_EMPLOYEE_ID_FILE, $employeeId);
}

function getPairingEmployeeId()
{
    return file_get_contents(PAIRING_EMPLOYEE_ID_FILE);
}

function pairEmployeeWithRfid($rfidSerial, $employeeId)
{
    removeRfidFromEmployee($rfidSerial);
    $query = "INSERT INTO employee_rfid(employee_id, rfid_serial) VALUES (?, ?)";
    $db = getDatabase();
    $statement = $db->prepare($query);
    return $statement->execute([$employeeId, $rfidSerial]);
}

function removeRfidFromEmployee($rfidSerial)
{
    $query = "DELETE FROM employee_rfid WHERE rfid_serial = ?";
    $db = getDatabase();
    $statement = $db->prepare($query);
    return $statement->execute([$rfidSerial]);
}

function getEmployeeByRfidSerial($rfidSerial)
{
    $query = "SELECT e.id, e.name FROM employees e INNER JOIN employee_rfid
    ON employee_rfid.employee_id = e.id
    WHERE employee_rfid.rfid_serial = ?";

    $db = getDatabase();
    $statement = $db->prepare($query);
    $statement->execute([$rfidSerial]);
    return $statement->fetchObject();
}
function getEmployeeRfidById($employeeId)
{
    $query = "SELECT rfid_serial FROM employee_rfid WHERE employee_id = ?";
    $db = getDatabase();
    $statement = $db->prepare($query);
    $statement->execute([$employeeId]);
    return $statement->fetchObject();
}

function getReaderStatus()
{
    return file_get_contents(RFID_STATUS_FILE);
}

function setReaderStatus($newStatus)
{
    if (!in_array($newStatus, [RFID_STATUS_PAIRING, RFID_STATUS_READING])) {
        return;
    }

    file_put_contents(RFID_STATUS_FILE, $newStatus);
}

function getEmployeesWithAttendanceCount($start, $end)
{
    $query = "select employees.name, 
sum(case when status = 'presence' then 1 else 0 end) as presence_count,
sum(case when status = 'absence' then 1 else 0 end) as absence_count 
 from employee_attendance
 inner join employees on employees.id = employee_attendance.employee_id
 where date >= ? and date <= ?
 group by employee_id;";
    $db = getDatabase();
    $statement = $db->prepare($query);
    $statement->execute([$start, $end]);
    return $statement->fetchAll();
}

function saveAttendanceData($date, $employees)
{
    deleteAttendanceDataByDate($date);
    $db = getDatabase();
    $db->beginTransaction();
    $statement = $db->prepare("INSERT INTO employee_attendance(employee_id, date, status) VALUES (?, ?, ?)");
    foreach ($employees as $employee) {
        $statement->execute([$employee->id, $date, $employee->status]);
    }
    $db->commit();
    return true;
}

function deleteAttendanceDataByDate($date)
{
    $db = getDatabase();
    $statement = $db->prepare("DELETE FROM employee_attendance WHERE date = ?");
    return $statement->execute([$date]);
}
function getAttendanceDataByDate($date)
{
    $db = getDatabase();
    $statement = $db->prepare("SELECT employee_id, status FROM employee_attendance WHERE date = ?");
    $statement->execute([$date]);
    return $statement->fetchAll();
}


function deleteEmployee($id)
{
    $db = getDatabase();
    $statement = $db->prepare("DELETE FROM employees WHERE id = ?");
    return $statement->execute([$id]);
}

function updateEmployee($name, $id)
{
    $db = getDatabase();
    $statement = $db->prepare("UPDATE employees SET name = ? WHERE id = ?");
    return $statement->execute([$name, $id]);
}
function getEmployeeById($id)
{
    $db = getDatabase();
    $statement = $db->prepare("SELECT id, name FROM employees WHERE id = ?");
    $statement->execute([$id]);
    return $statement->fetchObject();
}

function saveEmployee($name)
{
    $db = getDatabase();
    $statement = $db->prepare("INSERT INTO employees(name) VALUES (?)");
    return $statement->execute([$name]);
}

function getEmployees()
{
    $db = getDatabase();
    $statement = $db->query("SELECT id, name FROM employees");
    return $statement->fetchAll();
}

function getVarFromEnvironmentVariables($key)
{
    if (defined("_ENV_CACHE")) {
        $vars = _ENV_CACHE;
    } else {
        $file = "env.php";
        if (!file_exists($file)) {
            throw new Exception("The environment file ($file) does not exists. Please create it");
        }
        $vars = parse_ini_file($file);
        define("_ENV_CACHE", $vars);
    }
    if (isset($vars[$key])) {
        return $vars[$key];
    } else {
        throw new Exception("The specified key (" . $key . ") does not exist in the environment file");
    }
}

function getDatabase()
{
    $password = getVarFromEnvironmentVariables("MYSQL_PASSWORD");
    $user = getVarFromEnvironmentVariables("MYSQL_USER");
    $dbName = getVarFromEnvironmentVariables("MYSQL_DATABASE_NAME");
    $database = new PDO('mysql:host=localhost;dbname=' . $dbName, $user, $password);
    $database->query("set names utf8;");
    $database->setAttribute(PDO::ATTR_EMULATE_PREPARES, FALSE);
    $database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $database->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
    return $database;
}
