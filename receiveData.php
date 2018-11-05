<?php

if(isset($_GET)){
    file_put_contents('receiveData.php',date("Y-m-d H:i:s")."[get]:".print_r($_GET,TRUE),FILE_APPEND);
}
if(isset($_POST)){
    file_put_contents('receiveData.php',date("Y-m-d H:i:s")."[post]:".print_r($_POST,TRUE),FILE_APPEND);
}