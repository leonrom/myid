<?php

date_default_timezone_set('UTC');

require_once './KLogger.php';
if (!$log){
    $log = new KLogger ( "log.txt" , KLogger::INFO, "G:i:s" );
}
$log->LogInfo("| start");
    
require_once './DB.php';
if (!$db){
    $db = new DB($log);
}
    
$http_origin = $_SERVER['HTTP_ORIGIN'];
$urlp = parse_url($http_origin);
$host = $urlp[host];

$allowed_hosts = [
 'ariturlearn.blogspot.com',
 'php1-leonrom.c9users.io',
];
  
header("Access-Control-Allow-Origin: $http_origin");
header("Access-Control-Allow-Credentials: true");
header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: X-Requested-With, ent-Range, Content-Disposition, Content-Description');
header('Access-Control-Max-Age: 1000');

if ((in_array($host, $allowed_hosts)) || ($host == ""))  // Decide if allow access
{
    $log->LogInfo("| allowed: $http_origin");
    $rez = '';
    if (isset($_REQUEST['parms'])) {
        $s = $_REQUEST["parms"];
        $parms = json_decode ($s);
        $log->LogInfo("| parms->type=$parms->type");
    
        switch ($parms->type) {
        case server_id:
	    	if (($user_name = $db->GetName( $parms->server_id)) != null){	
    		    if (!($history = $db->GetHistory( $parms->server_id))){
		            $history = '';
		        }
		        $rez = '{' . '"user_name":"' . $user_name . '",' . '"history":"' . $history . '",' . '"typ":"history"}';
	    	    $db->AddHistory($parms->server_id, 'page', '');
    		}
            break;
        case id_browse:  
    		if (($server_id = $db->AddUser( $parms->id_browse, $parms->user_name)) != null){	// добавил нового усера
                $rez = '{' . '"server_id":"' . $server_id . '",' . '"typ":"addUser"}';      // Вернул его server_id
	    	    $db->AddHistory($server_id, 'page', '');
    		}
            break;
        case user_name:
            if ( $server_id = $db->SetName( $parms->server_id, $parms->user_name)){
                $rez = '{"typ":"none"}';
            }
        }
    }
    $log->LogInfo("| rez=$rez");
    echo "=>" . $rez;
}else{
    $log->LogError("disallowed: $http_origin");
    echo "=<your fomain is not allowed";
}

?>