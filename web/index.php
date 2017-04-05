<?php
include_once('display.php');
include_once('statsServ.php');
include_once('syncology.php');

$CONF = parse_ini_file('conf/conf.ini');
?> 
<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8">
		<meta name="robots" content="noindex">
		<title>Start - AtomicBox.</title>
		<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
		<link rel="icon" type="ico" href="./assets/img/tardis.ico">
		<link rel="stylesheet" type="text/css" href="./assets/css/style.css">
		<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
	</head>
	<body>
	<h1 class="titre">Rsyncology</h1>
<?php
	$currentSync = readSending($CONF['RSYNC_SENDING']);
	$content = newSyncBloc($currentSync['title'],$currentSync['percentage'],$currentSync['size'],$currentSync['speed'],$currentSync['eta'],array('film1','film2','film3'));
	$section = newSection("En cours",$content,"box-synchro","Synchronisation");
	echo $section;
	
	$history_tab = update_history(get_json($CONF['SYNC_HISTORY']),$CONF['RSYNC_LOG_FILE']);
	$sync_history = newSyncHistoryBloc($history_tab);
	set_json($history_tab,$CONF['SYNC_HISTORY']);
	$section = newSection("Historique",$sync_history,"box-synchro","Historique");
	echo $section;
	
	$stats = newServerStatsBloc(getCpuLoad(),getHDDUsage(),getRamUsage()[0]);
	$section = newSection('Server Info<span class=uptime>Uptime: '.getUpTime().'</span>',$stats,'box-monitoring','Monitoring Section'); 
	echo $section;
?>

	</body>
</html>	
