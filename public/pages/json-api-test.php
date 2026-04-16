<?php

$apiKey  = "0LYYh/KUGcLOwfpOf/+JwkTu7F3fyLDLhPcT27LheWRNBa/b5Mq2PHWVgXYw+S1U";
$staffNo = "0530-09";

$url = "https://ebdr.upnm.edu.my/api/bdr-distance-record.php?staff_no=" . urlencode($staffNo);

$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "X-API-Key: $apiKey"
    ],
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo "Curl Error: " . curl_error($ch);
} else {
    // display raw response
    echo "<pre>";
    print_r(json_decode($response, true));
    echo "</pre>";
}

curl_close($ch);