<?php
/*******************************************/
/* 					Misc 				   */
/*******************************************/
function set_json($data,$file) {
	$item = json_encode($data);
	file_put_contents($file, $item);
}
function get_json($file){
	$json = file_get_contents($file);
	$data = json_decode($json, true);
	return $data;
}
/*******************************************/
/* 				Logs Reading			   */
/*******************************************/
/* Nom : readSending
 * Description : Extraction des informations sur le transfer en cours depuis le log de sortie Rsync.
 * 
 * @param : (String) $log => Fichier de log Rsync
 * 
 * @return : (Array) $rsyncTab => Tableau associatif contenant les informations. Clés : 'title','size','percentage','speed','eta'
 */
function readSending($log){
	if (file_exists($log)){
		$file = file($log,FILE_IGNORE_NEW_LINES);
		if (strstr($file[0],"Pas de fichier")){
			$rsyncInfo = array('title' => "Pas de transfer en cours",
				'size' => "",
				'percentage' => "0%",
				'speed' => "",
				'eta' => "");
		} else {
			// On split la dernière ligne, délimiteur ^M et on parse les informations contenues
			$rsyncTabs = explode(chr(13),$file[count($file)-1]);
			$nbEntry = count($rsyncTabs);
			// Fix si la dernière case du tableau est vide
			$i = 1;
			if ($rsyncTabs[$nbEntry-$i] == ""){
				$i++;
			}
			// Si la sortie est imcomplète, on remonte a la précédente
			do {
				$rsyncInfo = readRsync($rsyncTabs[$nbEntry-$i]);
				$i++;
			} while ($rsyncInfo['size'] == "" or $rsyncInfo['percentage'] == "" or $rsyncInfo['speed'] == "" or $rsyncInfo['eta'] == "" or $i == $nbEntry);
			// On recupère le titre et la taille
			$filename = $file[0];
			$rsyncInfo['title'] = array_pop(explode("/",$filename));
			$rsyncInfo['size'] = sizeFormat(stat($file[0])['size']);
		}
	} else {
		$rsyncInfo = array('title' => "Impossible d'ouvrir le fichier $log .",
			'size' => "",
			'percentage' => "0%",
			'speed' => "",
			'eta' => "");
	}
	return $rsyncInfo;
}
/****************************/
/* Nom : readRsync
 * Description : Extraction des informations sur le transfer depuis une ligne de sortie rsync
 * 
 * @param : (String) $line => Ligne de sortie de RSync (ex:   1,776,713,728  79%   10.45MB/s    0:00:43)
 * 
 * @return : (Array) $rsyncTab => Tableau associatif contenant les informations. Clés : 'size','percentage','speed','eta'
 */
function readRsync($line){
	$line=preg_replace('/^[\s]+/','',$line);
	$info=preg_split('/[\s]+/',$line);
	$rsyncTab = array(
		"size" => str_replace(',','',$info[0]), // On enlève les virgule dans la taille
		"percentage" => $info[1],
		"speed" => $info[2],
		"eta" => $info[3]
	);
	return $rsyncTab;
}
/****************************/
/* Nom : readRsyncLogs
 * Description : Lecture des fichiers de sorties de rsync et écriture dans la base d'historique
 * 
 * @param : (String) $LOG_FOLDER => Dossier contenant les logs du script rsync
 * 			(String) $RSYNC_LOG_FILE => Nom de base du fichier de log Rsync (hors timestamp)
 * 			(String) $HISTORY_DB => Fichier d'historique local où sont stockés les derniers envois
 * 
 * @return : Ecriture dans le fichier d'historique. Format de ligne : "Date;Fichier Envoyé;Taille;Vitesse Moyenne"
 * 
 * TODO : Correction des droits pour suppression des fichiers en non root.
 */
function readRsyncLogs($LOG_FOLDER,$RSYNC_LOG_FILE){
	$transfers = array();
	if ($folder = opendir($LOG_FOLDER)){
		while(false !== ($file = readdir($folder))){
			if (strstr($file,$RSYNC_LOG_FILE.".")){
				$tabFile = file($LOG_FOLDER."/".$file);
				if(strstr($tabFile[0],'Film')){
					$type = 'movie';
				} 
				if(strstr($tabFile[0],'Serie')){
					$type = 'show';
				}
				$sentFileTab = explode("/",trim($tabFile[0]));
				$sentFile = array_pop($sentFileTab);
				$rsyncStatsTab = explode(chr(13),$tabFile[count($tabFile)-1]); // On sépare les entrée délimités par ^M
				$average = averageCalc($rsyncStatsTab);
				$size = sizeFormat(readRsync($rsyncStatsTab[count($rsyncStatsTab)-1])['size']); // On recupère la taille a la derniere ligne (100%)
				$date = str_replace($RSYNC_LOG_FILE.'.','',$file);
				$rsyncStat = array('date' => $date,
							  'file' => $sentFile,
							  'size' => $size,
							  'speed' => $average,
							  'title' => $sentFile,
							  'type' => $type);
				echo '----------------------'."\n";
				array_push($transfers,$rsyncStat);
				#unlink($LOG_FOLDER."/".$file);
			}
		}
		closedir($folder);
	}
	return $transfers;
}
function update_history($transfers,$logfile){
	$current_epoch = time();
	$movies = $transfers['movie'];
	$shows = $transfers['show'];
	// Checking movie
	foreach ($movies as $key => $movie){
		// Remove transfert older than one week.
		if (intval($movie['date']) < ($current_epoch - 604800)){
			unset($movies[$key]);
		}
	}
	// Checking shows
	foreach ($shows as $key => $show){
		// Remove transfert older than one week.
		if (intval($show['date']) < ($current_epoch - 604800)){
			unset($shows[$key]);
		}
	}
	$transfers = readRsyncLogs(dirname($logfile),basename($logfile));
	foreach ($transfers as $transfer){
		if ($transfer['type'] == 'movie'){
			unset($transfer['type']);
			array_push($movies,$transfer);
		} elseif ($transfer['type'] == 'show'){
			unset($transfer['type']);
			array_push($shows,$transfer);
		}		
	}
	$history_tab['movie']=$movies;
	$history_tab['show']=$shows;
	return $history_tab;
}
/*******************************************/
/* 					Calculs				   */
/*******************************************/
/* Nom : sizeFormat
 * Description : Formatage d'un nombre en octets en unité plus facilement compréhensible (Ko,Mo,Go ...) 
 * 
 * @param : (Float) $size => Taille en octets
 * 
 * @return : (String) $size => Taille convertie avec son unité.
 */
function sizeFormat($size){
	$unit = array('o','Ko','Mo','Go');	
	$u = 0;
	while ($size > 1024){
		$size = $size / 1024;
		$u++;
	}
	return round($size,2)." ".$unit[$u];
}
/****************************/
/* Nom : averageCalc
 * Description : Calcule la vitesse moyenne d'envoi a partir de chaque ligne de sortie rsync
 * 
 * @param : (Array) $rsyncStatsTab => Tableau contenant chaque ligne de sortie de Rsync. 
 * 
 * @return : (String) $average => Vitesse moyenne de transfer (avec unité)
 */
function averageCalc($rsyncStatsTab){
	$nbStat = count($rsyncStatsTab) ;
	$sommeVitesse = 0;
	for($i = 2;$i < $nbStat - 1;$i++){ // On lit tout sauf premiere ligne (vitesse à 0)
		$rsyncStatTab = readRsync($rsyncStatsTab[$i]);
		// Conversion d'unité en B/s pour calcul		
		if (strstr($rsyncStatTab["speed"],"GB/s")){
			$vitesse = floatval($rsyncStatTab["speed"])*1073741824;
		}	
		elseif (strstr($rsyncStatTab["speed"],"MB/s")){
			$vitesse = floatval($rsyncStatTab["speed"])*1048576;
		}
		elseif (strstr($rsyncStatTab["speed"],"KB/s")){
			$vitesse = floatval($rsyncStatTab["speed"])*1048576;
		}
		else {	
			$vitesse = floatval($rsyncStatTab["speed"]);
		}
		$sommeVitesse += $vitesse; 
	}
	$average = $sommeVitesse / ($nbStat -2);
	return sizeFormat($average)."/s";
}
?>
