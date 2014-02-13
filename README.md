simplepdo
=========

Simple PHP PDO ( for mysql ) wrapper for interactive/mindless usage

Usage
=====

    <?php
    require_once "simplepdo.php";

    $_SESSION["simplepdo.rwconnect"]="mysql:host=localhost;dbname=mydb;charset=utf8#user#pass";
    $_SESSION["simplepdo.roconnect"]="mysql:host=localhost;dbname=mydb;charset=utf8#user#pass";

    echo dbprint(dbquery("select * from yourtable where weekday=? order by datefield desc limit 11",array("sunday")));
    echo dbprint(array(array("1234","12","11")),false,array("mar"=>0,"sep"=>"|","nl"=>""));
    dbiter("select id from users where class=? dateon>date_sub(now(),interval 7 day)",
           array("student"),
           function($row)use($othervar){
             list($id)=$row;
             dbexec("update students where id=? set active=?",array($id,1));
           });
