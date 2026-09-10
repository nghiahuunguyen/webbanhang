<?php
//$sql="insert into Role(name) values('Admin')";
//$sql="insert into Role(name) values('$name')";=> $name='Admin => sql injction => join =>framwork(Laravel)=>fix
//$name='Admin=>\'Admin
//fix sql injection=>$sql ="ghi cau leenh sql vao"
function fixSqlInjection($sql){
    $sql=str_replace('\\','\\\\',$sql);
    $sql=str_replace('\'','\\\'',$sql);
    return $sql;
}
function getGet($key) {
    $value='';
    if (isset($_GET[$key])) {
        $value=$_GET[$key];
        $value= fixSqlInjection($value);
    }
    return $value;
}

function getRequest($key) {
    $value='';
    if (isset($_REQUEST[$key])) {
        $value=$_REQUEST[$key];
        $value= fixSqlInjection($value);
    }
    return $value;
}

function getPost($key) {
    $value='';
    if (isset($_POST[$key])) {
        $value=$_POST[$key];
        $value= fixSqlInjection($value);
    }
    return $value;
}

function getCookie($key) {
    $value='';
    if (isset($_COOKIE[$key])) {
        $value=$_COOKIE[$key];
        $value= fixSqlInjection($value);
    }
    return $value;
}