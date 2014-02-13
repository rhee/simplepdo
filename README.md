simplepdo
=========

Simple PHP PDO ( for mysql ) wrapper for interactive/mindless usage

Usage
=====

    <?php
    require_once "simplepdo.php";

    $_SESSION["simplepdo.rwconnect"]="mysql:host=localhost;dbname=wps;charset=utf8#user#pass";
    $_SESSION["simplepdo.roconnect"]="mysql:host=localhost;dbname=wps;charset=utf8#user#pass";

    echo dbprint(dbquery("select * from yourtable where weekday=? order by datefield desc limit 11",array("sunday")));
