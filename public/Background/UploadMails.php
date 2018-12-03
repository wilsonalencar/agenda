<?php

echo "Upload";exit;
$url = $_SERVER['HTTP_HOST'].'/upload/job';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

echo "<PRe>";
print_r($response);exit;


// $ch = curl_init();
// $link = $_SERVER['HTTP_HOST'].'/upload/job';
// curl_setopt($ch, CURLOPT_URL, $link);
// curl_setopt($ch, CURLOPT_HEADER, 0);
// curl_exec($ch);
// curl_close($ch);
?>