<?php

// prevent anyone from accessing this file directly
defined('ABSPATH') or die('Access Denied!');

// create debug/logging function
// you can use this function to write to the wordpress debug log
// example:
//    write_log('some logging data');
if (!function_exists('write_log')) {
    function write_log($log){
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
    }
}

// create https based email message (using the varmail internet service)
// https://varmail.me/
// you can use this function to send an email via https for test purposes
// example:
//    post_email('some email subject', 'hello world!', '63677'); [reqid is optional]
//    post_email('some email subject', 'hello world!');
if (!function_exists('post_email')) {
    function post_email($subject, $message, $token = null, $reqid = null){
        // https://varmail.me/
        // curl https://varmail.me/<api_key> \
        // -H 'Content-type: application/json' \
        // -d '{
        //    "subject": "Varmail is fun!",
        //    "text": "Some message content...",
        //    "_reqid": "3361"
        //  }
        //wp_mail('tecgent@gmail.com', $subject, $message);

        if (true === WP_DEBUG) {
            if (!empty($token) && $token != null) {

                $url = "https://varmail.me/{$token}";

                $data = array(
                   'subject' => '"'.$subject.'"',
                   'text' => '"'.$message.'"',
                   '_reqid' => '"'.$reqid.'"',
                );
                $data_string = json_encode($data);
                write_log("VARMAIL DATA: {$data_string}");

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: '.strlen($data_string)));

                $result = curl_exec($ch);
                //TODO:REMOVE
                //write_log("varmail result: {$result}");
            }
        }
    }
}
