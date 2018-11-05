<?php

if(isset($_GET)){
    file_put_contents('receiveData.txt',date("Y-m-d H:i:s")."[get]:".print_r($_GET,TRUE),FILE_APPEND);
}
if(isset($_POST)){
    file_put_contents('receiveData.txt',date("Y-m-d H:i:s")."[post]:".$_POST,FILE_APPEND);
}
