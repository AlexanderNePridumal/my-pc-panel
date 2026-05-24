<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
$csvUrl = "https://docs.google.com/spreadsheets/d/e/2PACX-1vTdx2B6KMZldMKf_mGRigmL0AqP0DtaNmaHeJhJQI31AJgd1hIgRMFk_Mv5DlDKm0AzI_mJF0Lfg7Ev/pub?output=csv&t=" . time();
$scriptUrl = "https://script.google.com/macros/s/AKfycbxRtkyOsY-WFJ1mki8aa9Dk7H6tu6Oe2Rk9-4XJo7nwNVXLQvLuyopzdWPQPBT_g_LwHA/exec";

if (isset($_GET['action'])) {
    $ch = curl_init($scriptUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($_POST));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
    header("Location: /"); exit;
}

$data = @file_get_contents($csvUrl);
$rows = array_map('str_getcsv', explode("\n", trim($data)));
$devices = [];
foreach ($rows as $row) {
    if(count($row) >= 4) {
        $ip = trim($row[1]);
        $devices[$ip] = ['time' => $row[0], 'status' => $row[2], 'name' => trim($row[3]), 'img' => $row[4]];
    }
}
?>
<td><?= !empty($d['img']) ? "📸 Есть" : "—" ?></td>
