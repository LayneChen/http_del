<?php

function get_client_ip(){
    $IP = '';
    if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
        $IP = getenv('HTTP_CLIENT_IP');
    } elseif(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
        $IP = getenv('HTTP_X_FORWARDED_FOR');
    } elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
        $IP = getenv('REMOTE_ADDR');
    } elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
        $IP = $_SERVER['REMOTE_ADDR'];
    }
    return $IP ? $IP : "unknow";
}

file_put_contents('receiveData.txt',date("Y-m-d H:i:s")."[header]:".print_r(get_client_ip(),TRUE),FILE_APPEND);

$body = file_get_contents("php://input");
if(isset($_REQUEST)){
    file_put_contents('receiveData.txt',date("Y-m-d H:i:s")."[request]:".print_r($_REQUEST,TRUE),FILE_APPEND);
}
if(isset($body)){
    file_put_contents('receiveData.txt',date("Y-m-d H:i:s")."[body]:".print_r($body,TRUE),FILE_APPEND);
}
if(isset($_GET)){
    file_put_contents('receiveData.txt',date("Y-m-d H:i:s")."[get]:".print_r($_GET,TRUE),FILE_APPEND);
}
if(isset($_POST)){
    file_put_contents('receiveData.txt',date("Y-m-d H:i:s")."[post]:".print_r($_POST,TRUE),FILE_APPEND);
}