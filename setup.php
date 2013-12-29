<?php
	/*
		Based on: StarOS Map
		Description: this file is used only for setup and update the starmap
		License: http://creativecommons.org/licenses/by/3.0/legalcode
		Version: 0.1 
		Date: 2013-12-28
		By Blackcancer, edited by gravypod
		website: initsysrev.net
		support: blackcancer@initsysrev.net
	*/
	include_once "includes/SMDecoder.php";
	
	if(count($argv) < 2){
		echo "Enter the directory of your starmade install followed by an optional list of types you would like excluded.\n";
		echo "The recomended example is: \"php " . implode(" ", $argv) . " /home/starmade/ 3,4,5\".\n";
		echo "1 = shops, 2 = space station, 3 = asteroid, 4 = planet, 5 = ships.\n";
		die();
	}
	
	$decoder = new SMDecoder();
	
	/*
		1 = shops
		2 = space station
		3 = asteroid
		4 = planet
		5 = ships
	*/
	
	$excludedTypes = array(
		5
	);
	
	$starmadeDirectory = $argv[1];
	
	if (isset($argv[2])) {
		$excludedTypes = explode(",", $argv[2]);
	}
	
	error_reporting(E_ERROR | E_PARSE); // Disable warnings to deal with SMDecoder
	
	$serverDatabase = $starmadeDirectory . "server-database/";
	echo "Now loading entity and player files\n";
	createEntityDatabase($serverDatabase, $decoder);
	echo "Now loading faction information\n";
	createFactionDatabase($serverDatabase, $decoder);
	
	function createEntityDatabase($dir, $decoder) {
		
		global $excludedTypes;
		
		$entityFiles = glob($dir . "ENTITY_*", GLOB_NOSORT); // Find all of the playerstate files
		$entities = array();
		$players = array();
		
		foreach ($entityFiles as $entity) {
			
			$ext = pathinfo($entity, PATHINFO_EXTENSION);
			
			if (!($ext == "ent")) {
				continue;
			}
			
			if (!(strpos($entity, 'ENTITY_PLAYER') === false) && !(strpos($entity, 'ENTITY_PLAYERSTATE_') === false) ) {
				$ent = $decoder->decodeSMFile($entity);
				$players[$ent['name']] = $ent;
				continue;
			}
			
			$ent = $decoder->decodeSMFile($entity);
			
			$type = $ent['type'];
			
			if (in_array(intval($type), $excludedTypes)) {
				continue;
			}
			
			$entities[$ent["uid"]] = $ent;
		}
		
		file_put_contents("./entities.json", json_encode($entities));
		file_put_contents("./players.json", json_encode($players));
		
	}
	
	function createFactionDatabase($dir, $decoder) {
		
		$ent = $decoder->decodeSMFile($dir . "FACTIONS.fac");
		$factions = array();
		
		foreach ($ent as $faction) {
			$factions[$faction['uid']] = $faction;
		}
		
		file_put_contents("./factions.json", json_encode($factions, JSON_FORCE_OBJECT));
		
	}
	
?>