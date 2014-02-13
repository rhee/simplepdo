<?php
$_SESSION["simplepdo.debug"]=getenv("SIMPLEPDO_DEBUG");

function rodb(){
  if(!isset($_SESSION["simplepdo.rodb"])){
    $roconn=@$_SESSION["simplepdo.roconnect"];
    if(!$roconn)$roconn="mysql:host=localhost;dbname=wps;charset=utf8##";
    list($url,$user,$pass)=explode("#",$roconn);
    $_SESSION["simplepdo.rodb"]=new PDO($url,$user,$pass,array(
	//PDO::ATTR_PERSISTENT=>true,
	PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
    ));
    //if(ROINIT)$_SESSION["simplepdo.rodb"]->exec(ROINIT);
  }
  return $_SESSION["simplepdo.rodb"];
}
function rwdb(){
  if(!isset($_SESSION["simplepdo.rwdb"])){
    $rwconn=@$_SESSION["simplepdo.rwconnect"];
    if(!$rwconn)$rwconn="mysql:host=localhost;dbname=wps;charset=utf8##";
    list($url,$user,$pass)=explode("#",$rwconn);
    $_SESSION["simplepdo.rwdb"]=new PDO($url,$user,$pass,array(
	//PDO::ATTR_PERSISTENT=>true,
	PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
    ));
    //if(RWINIT)$_SESSION["simplepdo.rwdb"]->exec(RWINIT);
  }
  return $_SESSION["simplepdo.rwdb"];
}
function dbexec($query,$args=false){
  if(!$args)$args=array();
  for($i=1;$i<=3;$i++){
    try{
      $db=rwdb();
if($_SESSION["simplepdo.debug"])error_log("dbexec: query=\"".implode(" ",explode("\n",$query))."\"");
if($_SESSION["simplepdo.debug"])error_log("dbexec: args=".json_encode($args));
      $res=$db->prepare($query)->execute($args);
      if($res)return true;
    }catch(PDOException $e){
      error_log("dbexec[".$i."]: ".$e->getMessage()." query:".$query);
    }
  }
  return false;
}
function dbquery($query,$args=false){
  if(!$args)$args=array();
  try{
    $db=rodb();
    $db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

if($_SESSION["simplepdo.debug"])error_log("dbquery: query=\"".implode(" ",explode("\n",$query))."\"");
if($_SESSION["simplepdo.debug"])error_log("dbquery: args=".json_encode($args));
    $st=$db->prepare($query);
    $st->execute($args);
    $ans=$st->fetchAll(PDO::FETCH_NUM);
  }catch(PDOException $e){
    error_log("dbquery: ".$e->getMessage()." query:".$query);
    return false;
  }
  return $ans;
}
function roiter($query,$args=false,$callback=false){
  if(!$args)$args=array();
  try{
    $db=rodb();
    $db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

if($_SESSION["simplepdo.debug"])error_log("roiter: query=\"".implode(" ",explode("\n",$query))."\"");
if($_SESSION["simplepdo.debug"])error_log("roiter: args=".json_encode($args));
    $st=$db->prepare($query);
    $st->execute($args);
    while($row=$st->fetch(PDO::FETCH_NUM)){
      //error_log("roiter: row=".json_encode($row));
      if($callback){
        $callback($row);
      }
    }
  }catch(PDOException $e){
    error_log("roiter: ".$e->getMessage()." query:".$query);
    return false;
  }
  return true;
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
