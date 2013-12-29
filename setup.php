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
	
	$starmadeDirectory = $argv[1];
	
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
			
			$type = $ent['Type'];
			
			if (in_array(intval($type), $excludedTypes)) {
				continue;
			}
			
			$sectorPosition = $ent['sPos']['x'].','.$ent['sPos']['y'].','.$ent['sPos']['z'];
			$localPosition = $ent['LocalPos']['x'].','.$ent['LocalPos']['y'].','.$ent['LocalPos']['z'];
			
			array_push($entities, array(
				'UID' => $ent['UID'],
				'type' => $type,
				'name' => $ent['Name'],
				'fid' => $ent['FactionID'],
				'creator' => $ent['Creator'],
				'lastMod' => $ent['Last_Mod'],
				'mass' => $ent['mass'],
				'pw' => $ent['pw'],
				'sh' => $ent['sh'],
				'sPos' => $sectorPosition,
				'localPos' => $localPosition,
				'genID' => $ent['Gen_ID']
			));
		}
		file_put_contents("./entities.json", json_encode($entities));
	}
	
	function createPlayerDatabase($dir, $decoder) {
		
		$playerFiles = glob($dir . "ENTITY_PLAYERSTATE_*.ent"); // Find all of the playerstate files
		$players = array();
		foreach ($playerFiles as $player) {
			$ent = $decoder->decodeSMFile($player);
			$sPos = $ent['Sector']['x'] . ',' . $ent['Sector']['y'] . ',' . $ent['Sector']['z'];
			array_push($players, array(
				'Name' => $ent['Name'],
				'Credits' => $ent['Credits'],
				'Sector' => $sPos,
				'fid' => $ent['FactionID']
			));
		}
		file_put_contents("./players.json", json_encode($players));
	}
	
	function createFactionDatabase($dir, $decoder) {
		$ent = $decoder->decodeSMFile($dir . "FACTIONS.fac");
		$factions = array();
		foreach ($ent as $faction) {
			
			array_push($factions, array(
				'ID' => $faction['ID'],
				'UID' => $faction['UID'],
				'name' => $faction['Name'],
				'home' => $faction['Home'],
				'r0' => $faction['Rank0'],
				'r1' => $faction['Rank1'],
				'r2' => $faction['Rank2'],
				'r3' => $faction['Rank3'],
				'r4' => $faction['Rank4']
			));
		}
		file_put_contents("./factions.json", json_encode($factions));
	}
	
?>