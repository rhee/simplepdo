<?php
$_SESSION["simplepdo.debug"]=getenv("SIMPLEPDO_DEBUG");

function rodb($connect=false){
  if($connect && $connect != @$_SESSION["simplepdo.roconnect"]){
    unset($_SESSION["simplepdo.rodb"]);
    $_SESSION["simplepdo.roconnect"]=$connect;
  }
  if(!isset($_SESSION["simplepdo.rodb"])){
    $roconn=@$_SESSION["simplepdo.roconnect"];
    if(!$roconn)$roconn="mysql:host=localhost;dbname=wps;charset=utf8##";
    list($url,$user,$pass)=explode("#",$roconn);
    $_SESSION["simplepdo.rodb"]=new PDO($url,$user,$pass,array(
	//PDO::ATTR_PERSISTENT=>true,
	PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
    ));
  }
  return $_SESSION["simplepdo.rodb"];
}
function rwdb($connect=false){
  if($connect && $connect != @$_SESSION["simplepdo.rwconnect"]){
    unset($_SESSION["simplepdo.rwdb"]);
    $_SESSION["simplepdo.rwconnect"]=$connect;
  }
  if(!isset($_SESSION["simplepdo.rwdb"])){
    $rwconn=@$_SESSION["simplepdo.rwconnect"];
    if(!$rwconn)$rwconn="mysql:host=localhost;dbname=wps;charset=utf8##";
    list($url,$user,$pass)=explode("#",$rwconn);
    $_SESSION["simplepdo.rwdb"]=new PDO($url,$user,$pass,array(
	//PDO::ATTR_PERSISTENT=>true,
	PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
    ));
  }
  return $_SESSION["simplepdo.rwdb"];
}
function dbexec($query,$args=false){
  //error_log("dbexec: ".json_encode(func_get_args()));
  if(!$args)$args=array();
  $start=microtime(true);
  for($i=1;$i<=3;$i++){
    try{
      $db=rwdb();
      $res=$db->prepare($query)->execute($args);
      $ans=!!$res;
      break;
    }catch(PDOException $e){
      error_log("dbexec[".$i."]: ".$e->getMessage()." query: ".json_encode(func_get_args()));
      $ans=false;
    }
  }
  $elapsed=microtime(true)-$start;
  if($elapsed>2.0){
    error_log("dbexec: elapsed: ".number_format($elapsed,1)." query: ".json_encode(func_get_args()));
  }
  return $ans;
}
function dbquery($query,$args=false,$flag=false){
  //error_log("dbquery: ".json_encode(func_get_args()));
  if(!$args)$args=array();
  if(!$flag)$flag=PDO::FETCH_NUM;
  $start=microtime(true);
  try{
    $db=rodb();
    $db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    $st=$db->prepare($query);
    $st->execute($args);
    $ans=$st->fetchAll($flag);
  }catch(PDOException $e){
    error_log("dbquery: ".$e->getMessage()." query: ".json_encode(func_get_args()));
    $ans=false;
  }
  $elapsed=microtime(true)-$start;
  if($elapsed>2.0){
    error_log("dbquery: elapsed: ".number_format($elapsed,1)." query: ".json_encode(func_get_args()));
  }
  return $ans;
}
function dbiter($query,$args=false,$callback=false,$flag=false){
  //error_log("dbiter: ".json_encode(func_get_args()));
  if(!$args)$args=array();
  if(!$flag)$flag=PDO::FETCH_NUM;
  $start=microtime(true);
  try{
    $db=rodb();
    $db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
    $st=$db->prepare($query);
    $st->execute($args);
    while($row=$st->fetch($flag)){
      if($callback){
        $callback($row);
      }
    }
    $ans=true;
  }catch(PDOException $e){
    error_log("dbiter: ".$e->getMessage()." query: ".json_encode(func_get_args()));
    $ans=false;
  }
  $elapsed=microtime(true)-$start;
  if($elapsed>2.0){
    error_log("dbiter: elapsed: ".number_format($elapsed,1)." query: ".json_encode(func_get_args()));
  }
  return $ans;
}
function dbprint($data,$header=false,$opt=false)
{
  $mar=1;  // default margin
  $sep=" "; // separator
  $vsp="_"; // vertical ( header / data ) sep
  $nl="\n"; // newline

  if(isset($opt["mar"]))$mar=$opt["mar"];
  if(isset($opt["sep"]))$sep=$opt["sep"];
  if(isset($opt["vsp"]))$vsp=$opt["vsp"];
  if(isset($opt["nl"]))$nl=$opt["nl"];

  $widths=array(); #if(isset($data[0]))$widths=array_fill(0,count($data[0]),0);

  if($mar>0){
    //get max width each col
    foreach($data as $row){
      for($i=0;$i<count($row);$i++){
	$w=$mar+strlen("".$row[$i]); // cell width
	if($w>@$widths[$i]){
	  $widths[$i]=$w;
	}
      }
    }
    if($header){
      for($i=0;$i<count($header);$i++){
	$w=$mar+strlen("".$header[$i]); // header cell width
	if($w>@$widths[$i]){
	  $widths[$i]=$w;
	}
      }
    }
  }

  $output="";

  //print header
  if($header){
    $houtput=implode($sep,array_map(function($cell,$width){return str_pad($cell,@$width," ",STR_PAD_RIGHT);},$header,$widths));
    $output.=$houtput .$nl;
    if($vsp){
      $output.=str_pad("",strlen($houtput),$vsp) .$nl;
    }
  }

  //print data
  foreach($data as $row){
    $doutput=implode($sep,array_map(function($cell,$width){return str_pad($cell,@$width," ",STR_PAD_LEFT);},$row,$widths));
    $output.=$doutput .$nl;
  }
  return $output;
}
