<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ================= DB CONNECTION =================
$conn = new mysqli(
    "localhost",
    "NMIMS_LPs",
    "&MUr*SAru]fAUDl[",
    "NMIMS_LPs"
);

if ($conn->connect_error) {
    die("DB Connection Failed: " . $conn->connect_error);
}

// ================= GET DATA =================
$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$mobile  = trim($_POST['mobile'] ?? '');
$state   = trim($_POST['state'] ?? '');
$program = trim($_POST['program'] ?? '');

$utm_source   = $_POST['utm_source'] ?? '';
$utm_medium   = $_POST['utm_medium'] ?? '';
$utm_campaign = $_POST['utm_campaign'] ?? '';
$utm_adgroup  = $_POST['utm_adgroup'] ?? '';
$utm_term     = $_POST['utm_term'] ?? '';
$utm_content  = $_POST['utm_content'] ?? '';

$gclid    = $_POST['gclid'] ?? '';
$page_url = $_POST['page_url'] ?? '';
$user_ip  = $_SERVER['REMOTE_ADDR'] ?? '';

// ================= VALIDATION =================
if (empty($name) || empty($email) || empty($mobile)) {
    die("Required fields missing");
}

// ================= INSERT INTO DB =================
$stmt = $conn->prepare("
INSERT INTO NMIMS_online_MBA
(
name,
email,
mobile,
program,
state,
utm_source,
utm_medium,
utm_campaign,
utm_adgroup,
utm_term,
utm_content,
gclid,
page_url,
user_ip
)
VALUES
(?,?,?,?,?,?,?,?,?,?,?,?,?,?)
");

if (!$stmt) {
    die("Prepare Failed: " . $conn->error);
}

$stmt->bind_param(
    "ssssssssssssss",
    $name,
    $email,
    $mobile,
    $program,
    $state,
    $utm_source,
    $utm_medium,
    $utm_campaign,
    $utm_adgroup,
    $utm_term,
    $utm_content,
    $gclid,
    $page_url,
    $user_ip
);

if (!$stmt->execute()) {
    die("DB Insert Failed: " . $stmt->error);
}

$stmt->close();


// ================= SFDC API =================
$sfdc_url = "https://business-agility-9703.my.salesforce-sites.com/services/apexrest/leadCreationAPI";

$data = [
    "name" => $name,
    "email" => $email,
    "phone" => $mobile,
    "state" => $state,

    "LeadSource" => "Google",
    "Lead_Vendor_Source" => "NMIMS",

    "EnquiredforProgram" => $program,
    "EnquiredforUniversity" => "NMIMS",

    "SourceCampaign" => $utm_campaign,
    "SourceContent" => $utm_content,
    "SourceMedium" => $utm_medium,
    "SourceIPAddress" => $user_ip,

    "utm_term" => $utm_term,
    "utm_source" => $utm_source,
    "utm_adgroup" => $utm_adgroup,

    "mx_utm_gclid" => $gclid
];

// ================= CURL =================
$ch = curl_init($sfdc_url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json'
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CONNECTTIMEOUT => 10
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    die("CURL Error: " . curl_error($ch));
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

// ================= LOG =================
file_put_contents(
    "sfdc_log.txt",
    date('Y-m-d H:i:s') .
    "\nHTTP Code: " . $httpCode .
    "\nPayload: " . json_encode($data) .
    "\nResponse: " . $response .
    "\n\n",
    FILE_APPEND
);

// ================= REDIRECT =================
header("Location: thankyou.php");
exit;
?>