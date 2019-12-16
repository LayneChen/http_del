<?php



file_put_contents('receiveData.txt',date("Y-m-d H:i:s")."[header]:".print_r(getallheaders(),TRUE),FILE_APPEND);

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