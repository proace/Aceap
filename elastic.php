<?php
error_reporting(E_ALL);
$query = '{"sort":[{"createdDate":"desc"}
            ]}';
 $post_url = "http://35.232.39.95/elasticsearch/forums/forum/_search";
        $curl = curl_init($post_url);
        $headers = array(
            'Content-Type: application/json',
            'Authorization:  Basic  dXNlcjpNUjhtNUZnSkx5blg='
        );
        curl_setopt($curl, CURLOPT_URL, $post_url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS,$query);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        $data = curl_exec($curl);
        $reults = json_decode($data);

        print_r($reults);