<?php

/********************************************************/
/* 					Generics Blocs 					*/
/********************************************************/
function newBanner($content){
};
function newSection($title,$content,$class,$comment){
	$html= '		<!-- '.$comment.' !-->
		<div class="box '.$class.'">
			<div class="top">
				<div class="left"></div>
				<div class="right"></div>
				<div class="middle">
					<p class="box-title">'.$title.'</p>
				</div>
			</div>
			<div class="center">
				<div class="separator separator-top"></div>
					'.$content.'
				<div class="separator separator-bottom"></div>	
			</div>
			<div class="bottom">
				<div class="left">
				</div>
				<div class="right">
				</div>
				<div class="middle">
				</div>			
			</div>
		</div>';
	return $html;
}
function newInnerBloc($title,$content,$id,$comment){
	$html = '
			<div class="separator separator-top"></div>
			<!-- '.$comment.' !-->
			<div class=inner-bloc>
				'.$content.'
			</div>
			<div class="separator separator-bottom"></div>';
	return $html;
}

/********************************************************/
/* 					RSyncology Blocs 					*/
/********************************************************/
/* Current Transfer 
/*/
function newSyncBloc($file,$percent,$size,$speed,$eta,$waiting){
	/* Liste des fichiers en attente*/
	$count_waiting = 0;
	$html_waiting = '<ul>';
	foreach ($waiting as $waiting_file){
		$html_waiting = $html_waiting.'<li>'.$waiting_file.'</li>';
		$count_waiting++;
	}
	$html_waiting .= '</ul>';
	/* Synchronisation en cours */
	$html = '<div class="synchro">
					<div class="sync-file">'.$file.'</div>
					<div class="ui-progress-bar ui-container ui-progress-bar-sync" id="progress_bar">
						<div class="ui-progress ui-progress-sync" style="width: '.$percent.'">
							<span class="ui-label-sync">'.$percent.'</span>
						</div>
					</div>
					<div class="infosync">
						Taille: '.$size.'&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;Vitesse: '.$speed.'&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;Temps restant: '.$eta.'<br>
						<a href="#" class="hidden-span waiting-file">Fichiers en attente ('.$count_waiting.')
							<span>'.$html_waiting.'</span>
						</a>
					</div>
					
			</div>'; 
	return $html;
}
function newSyncHistoryBloc($synchrosTabs){
	$nbMovie = count($synchrosTabs['movie']);
	if ($nbMovie == 0){
		$movie = 'Pas de film récement envoyé.';
	} else {
		$movie = "";
		foreach ($synchrosTabs['movie'] as $movieTab){
			$movie = $movie.newSyncHistoryLine($movieTab);
		}
	}
	$nbShow = count($synchrosTabs['show']);
	if ($nbShow == 0){
		$show = 'Pas de série récement envoyée.';
	} else {
		$show = "";
		foreach ($synchrosTabs['show'] as $showTab){
			$show = $show.newSyncHistoryLine($showTab);
		}
	}	
	
	
	$html = '<div class=synchro-history>
					<div class="movie-history cat-history">
					<h3>Derniers films envoyés</h3>
						<ul>
						'.$movie.'
						</ul>
					</div>
					<div class="show-history cat-history">
					<h3>Dernieres séries envoyées</h3>
						<ul>
						'.$show.'
						</ul>					
					</div>
				</div>';
	return $html;
}
function newSyncHistoryLine($synchroTab){
	return '<li><a href="#" class="hidden-span">'.$synchroTab['title'].'
		<span>
			<ul class="hist-info">
				<li>Envoyé: '.$synchroTab['date'].'</li>
				<li>Taille: '.$synchroTab['size'].'</li>
				<li>Vitesse Moyenne: '.$synchroTab['speed'].'</li>
			</ul>
		</span></a></li>';
}
/********************************************************/
/* 					Monitoring Blocs 					*/
/********************************************************/
function newServerStatsBloc($cpu,$hdd,$ram){
	$html = '<div class=monitoring>';
	$html = $html.newServerStatBloc('CPU',$cpu);
	$html = $html.newServerStatBloc('HDD',$hdd);
	$html = $html.newServerStatBloc('RAM',$ram);
	$html = $html.'</div>';
	return $html;
}
function newServerStatBloc($title,$value){
	$html = '
					<div class="server-stats-unit">
						<span>'.$title.'</span>
						<div class="ui-progress-bar ui-container ui-progress-bar-stat" id="'.$title.'bar">
							<div class="ui-progress ui-progress-stat" style="width:'.$value.'%"></div>
						</div>
						<span>'.$value.'%</span>
					</div>';
	return $html;
}

?>
