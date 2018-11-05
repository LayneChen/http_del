<?php

$body = file_get_contents("php://input");
if(isset($_REQUEST)){
    file_put_contents('receiveData.txt',date("Y-m-d H:i:s")."[request]:".print_r($_REQUEST,TRUE),FILE_APPEND);
}
if(isset($body)){
    file_put_contents('receiveData.txt',date("Y-m-d H:i:s")."[body]:".print_r($body,TRUE),FILE_APPEND);
}