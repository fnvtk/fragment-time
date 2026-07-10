<?php
 echo 'xxx11x';

$url = 'http://api.quwanzhi.com/api/ckb/collect/postdata';
$params = [
    'key' => "2524e795-5c73-4405-903b-cf7bf9b09465111",
    'mobile' => '666666',
    'labels' => '666666'
];



$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL =>$url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => $params,
));

$response = curl_exec($curl);
curl_close($curl);
return $response;







?>