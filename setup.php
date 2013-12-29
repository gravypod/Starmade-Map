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
	
	if(count($argv) < 3){
		echo "Enter the directory of your starmade install followed by a list of types you would like excluded.\n";
		echo "The recomended example is: \"php " . implode(" ", $argv) . " /home/starmade/ 3,4,5\".\n";
		echo "1 = shops, 2 = space station, 3 = asteroid, 4 = planet, 5 = ships.\n";
		die();
	}
	
	$decoder = new SMDecoder();
	
	global $excludedTypes;
	
	/*
		1 = shops
		2 = space station
		3 = asteroid
		4 = planet
		5 = ships
	*/
	
	$excludedTypes = array(
		3, 
		4,
		5
	);
	
	$starmadeDirectory = realpath($argv[1]);
	
	if (isset($argv[2])) {
		$excludedTypes = explode(",", $argv[2]);
	}
	
	error_reporting(E_ERROR | E_PARSE); // Disable warnings to deal with SMDecoder
	
	$serverDatabase = $starmadeDirectory . "server-database/";
	echo "Now loading player files\n";
	createPlayerDatabase($serverDatabase, $decoder);
	echo "Now loading entity files\n";
	createEntityDatabase($serverDatabase, $decoder);
	echo "Now loading faction information\n";
	createFactionDatabase($serverDatabase, $decoder);
	
	function createEntityDatabase($dir, $decoder) {
		
		global $excludedTypes;
		
		$entityFiles = glob($dir . "ENTITY_*", GLOB_NOSORT); // Find all of the playerstate files
		$entities = array();
		
		foreach ($entityFiles as $entity) {
			
			if (!(strpos($entity, 'ENTITY_PLAYER') === false)) {
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
	}
	
	function createPlayerDatabase($dir, $decoder) {
		
		$playerFiles = glob($dir . "ENTITY_PLAYERSTATE_*.ent"); // Find all of the playerstate files
		$players = array();
		foreach ($playerFiles as $player) {
			$ent = $decoder->decodeSMFile($player);
			
			$players[$ent['name']] = $ent;
		}
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