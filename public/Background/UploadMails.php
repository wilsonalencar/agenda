<?php
$ch = curl_init();
$link = $_SERVER['HTTP_HOST'].'/upload/job';
curl_setopt($ch, CURLOPT_URL, $link);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_exec($ch);
curl_close($ch);
?>