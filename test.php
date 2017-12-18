<?php

/**
 * OnlineBiz Software Solution
 * 
 * @project lendclick.com.au
 * @version 0.0.1
 * @encoding UTF-8
 * @author Joe Vu<joe@onlinebizsoft.com>
 * @see http://onlinebizsoft.com
 * @copyright (c) 2017 , OnlineBiz Software Solution
 * 
 * Create at: Dec 18, 2017 2:44:09 PM
 */
$data = array('guid' => 1, 'video_title' => 'Video test', 'email' => 'example@gmail.com');
$jsonEncodedData = json_encode($data);
$opts = array(
    CURLOPT_URL => 'https://hooks.zapier.com/hooks/catch/2792664/s73m4u/',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POST => 1,
    CURLOPT_POSTFIELDS => $jsonEncodedData,
    CURLOPT_HTTPHEADER => array('Content-Type: application/json', 'Content-Length: ' . strlen($jsonEncodedData))
);
$curl = curl_init(); 
// Set curl options
curl_setopt_array($curl, $opts);

// Get the results
$result = curl_exec($curl);

// Close resource
curl_close($curl);
 
var_dump($result);
 