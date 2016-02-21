<?php
if ( ! isset($GLOBALS["simplepdo.debug"]) ) $GLOBALS["simplepdo.debug"]=getenv("SIMPLEPDO_DEBUG");
if ( ! isset($GLOBALS["simplepdo.slowquerycheck"]) ) $GLOBALS["simplepdo.slowquerycheck"]=1.1;

/* workaround for php < 5.4 */
if(!defined('JSON_UNESCAPED_UNICODE'))define('JSON_UNESCAPED_UNICODE',0);
if(!defined('JSON_UNESCAPED_SLASHES'))define('JSON_UNESCAPED_SLASHES',0);

function dberr(/*mixed*/ $e,/*array*/ $q=false)
{
    if(is_a($e,"Exception")) {
        error_log($e->getMessage());
        error_log($e->getTraceAsString());
    }else{
        error_log($e);
        $btlist = debug_backtrace();
        for($i = 1; $i < count($btlist); $i++){
            $bt = $btlist[$i];
            error_log("#$i {$bt["file"]}({$bt["line"]}): calls {$bt["function"]}()");
        }
    }
    if($q){
	if ( is_string($q[0]) ) {
	    $query = preg_replace('/\s+/', ' ', $q[0]);
	    $args = array_slice($q,1);
	    error_log("last query was: ".$query);
	    error_log("last query params: ".json_encode($args,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
	} else {
	    error_log("last query was: ".json_encode($q,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
	}
    }
}

function rodb($connect=false){
    if($connect && $connect != @$GLOBALS["simplepdo.roconnect"]){
        unset($GLOBALS["simplepdo.rodb"]);
        $GLOBALS["simplepdo.roconnect"]=$connect;
    }
    if(!isset($GLOBALS["simplepdo.rodb"])){
        $roconn=@$GLOBALS["simplepdo.roconnect"];
        if(!$roconn)$roconn="mysql:host=localhost;dbname=wps;charset=utf8##";
        list($url,$user,$pass)=explode("#",$roconn);
        $GLOBALS["simplepdo.rodb"]=new PDO($url,$user,$pass,array(
                    //PDO::ATTR_PERSISTENT=>true,
                    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
                    ));
    }
    return $GLOBALS["simplepdo.rodb"];
}
function rwdb($connect=false){
    if($connect && $connect != @$GLOBALS["simplepdo.rwconnect"]){
        unset($GLOBALS["simplepdo.rwdb"]);
        $GLOBALS["simplepdo.rwconnect"]=$connect;
    }
    if(!isset($GLOBALS["simplepdo.rwdb"])){
        $rwconn=@$GLOBALS["simplepdo.rwconnect"];
        if(!$rwconn)$rwconn="mysql:host=localhost;dbname=wps;charset=utf8##";
        list($url,$user,$pass)=explode("#",$rwconn);
        $GLOBALS["simplepdo.rwdb"]=new PDO($url,$user,$pass,array(
                    //PDO::ATTR_PERSISTENT=>true,
                    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
                    ));
    }
    return $GLOBALS["simplepdo.rwdb"];
}
function dbexec($query,$args=false){
    if(!$args)$args=array();
    $start=microtime(true);
    for($i=1;$i<=3;$i++){
        try{
            $db=rwdb();
            $res=$db->prepare($query)->execute($args);
            $ans=!!$res;
            break;
        }catch(PDOException $e){
            dberr($e,func_get_args());
            $ans=array();
        }
    }
    $elapsed=microtime(true)-$start;
    if($elapsed>0+@$GLOBALS["simplepdo.slowquerycheck"]){
        dberr("dbexec: elapsed: ".number_format($elapsed,1),func_get_args());
    }
    return $ans;
}
function dbquery($query,$args=false,$flag=false){
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
        dberr($e,func_get_args());
        $ans=array();
    }
    $elapsed=microtime(true)-$start;
    if($elapsed>0+@$GLOBALS["simplepdo.slowquerycheck"]){
        dberr("dbquery: elapsed: ".number_format($elapsed,1),func_get_args());
    }
    return $ans;
}
function dbget($query,$args=false,$flag=false){
    if(!$args)$args=array();
    if(!$flag)$flag=PDO::FETCH_NUM;
    $start=microtime(true);
    try{
        $db=rodb();
        $db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        $st=$db->prepare($query);
        $st->execute($args);
        try{
            $ans=$st->fetch($flag); /// GET first row
        }catch(PDOException $e){
            //XXX ignore deliberately to allow create/insert/update #dberr($e,func_get_args());
            $ans=array();
        }
    }catch(PDOException $e){
        dberr($e,func_get_args());
        $ans=array();
    }
    $elapsed=microtime(true)-$start;
    if($elapsed>0+@$GLOBALS["simplepdo.slowquerycheck"]){
        dberr("dbget: elapsed: ".number_format($elapsed,1),func_get_args());
    }
    return $ans;
}
function dbiter($query,$args=false,$callback=false,$flag=false){
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
        dberr($e,func_get_args());
        $ans=array();
    }
    $elapsed=microtime(true)-$start;
    if($elapsed>0+@$GLOBALS["simplepdo.slowquerycheck"]){
        dberr("dbiter: elapsed: ".number_format($elapsed,1),func_get_args());
    }
    return $ans;
}
function dbprint($data,$header=false,$opt=false)
{
    $mar=1;  // default margin
    $sep=" "; // separator
    $vsp="_"; // vertical ( header / data ) sep
    $nl="\n"; // newline
    $print=0; // not echo result

    if(isset($opt["mar"]))$mar=$opt["mar"];
    if(isset($opt["sep"]))$sep=$opt["sep"];
    if(isset($opt["vsp"]))$vsp=$opt["vsp"];
    if(isset($opt["nl"]))$nl=$opt["nl"];
    if(isset($opt["print"]))$print=$opt["print"];

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

    if($print){
        echo $output;
    }

    return $output;
}

function dbperf( $tag, $callback=false, $perf_finish = 0 )
{
    global $perf_count,$perf_lastcount,$perf_tstart,$perf_interval;

    if(!isset($perf_count)){
        $perf_count=$perf_lastcount=0;
        $perf_tstart=microtime(true);
        $perf_interval=5.0; //$interval;
    }

    $perf_count++;
    if(($tlap=microtime(true))-$perf_tstart>=$perf_interval){
        if($tlap-$perf_tstart>0){
            $telapsed=round($tlap-$perf_tstart,2);
            $cps=round(($perf_count-$perf_lastcount)/($tlap-$perf_tstart),2);

#error_log("$tag count: {$perf_count}, elapsed=$telapsed, speed=$cps steps/sec");

            if($perf_finish>0){
                $eta=(int)(($perf_finish-$perf_count)/$cps);
                if ( $eta >= 0 ) {
                    $eta_m=(int)($eta/60);
                    $eta_s=$eta%60;
                    error_log(sprintf("%s count: %d/%d, elapsed=%d, speed=%.1f steps/sec, eta=%d:%02d",$tag,$perf_count,$perf_finish,$telapsed,$cps,$eta_m,$eta_s));
                } else {
                    error_log(sprintf("%s count: %d, elapsed=%d, speed=%.1f steps/sec",$tag,$perf_count,$telapsed,$cps));
                }
            }else{
                error_log(sprintf("%s count: %d, elapsed=%d, speed=%.1f steps/sec",$tag,$perf_count,$telapsed,$cps));
            }

        }else{
            error_log("$tag count: {$perf_count}");
        }
        $perf_lastcount=$perf_count;
        $perf_tstart=$tlap;
        if(is_callable($callback))$callback($perf_count,$tlap);
    }
}

function dbclose()
{
    $GLOBALS["simplepdo.rodb"] = null;
    unset($GLOBALS["simplepdo.rodb"]);
    $GLOBALS["simplepdo.rwdb"] = null;
    unset($GLOBALS["simplepdo.rwdb"]);
}

// Emacs:
// Local Variables:
// mode: php
// c-basic-offset: 4
// End:
// vim: sw=4 sts=4 ts=8 et
