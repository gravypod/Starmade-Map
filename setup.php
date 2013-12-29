<?php
/*
   Product: StarOS Map
   Description: this file is used only for setup and update the starmap
   License: http://creativecommons.org/licenses/by/3.0/legalcode

   Version: 0.1								Date: 2013-12-28
   By Blackcancer
  
   website: 
   support: blackcancer@initsysrev.net
*/
	try {
		
		include("scripts/php/configs/StarOS_Config.php");
		include("scripts/php/class/SMDecoder.php");
		
		$SMDecoder = new SMDecoder();
		
		try{
			
			$db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASSWORD);
			$db->exec("SET CHARACTER SET utf8");
			
		} catch(Exception $e){
			
			$jsonErr = array('title'=>'MySQL error:', 'err' => $e->getMessage());
			die(json_encode($jsonErr));
			
		}
		
	} catch (Exception $e) {
		// Should probably write it to a log file, but... for brevity's sake:
		echo 'Caught exception: ',  $e->getMessage(), "\n";
		die();
	}
	
	if(count($argv) < 2){
		echo "Enter the game directory as 1st argument and the option (create_db, populate_db or update_db)\n";
		die();
	} else if(count($argv) < 3){
		echo "Enter the option (create_db, populate_db or update_db)\n";
		die();
	}
	
	if($argv[2] == 'create_db'){
		$db->exec(file_get_contents('scripts/sql/create/create_entity.sql'));
		$db->exec(file_get_contents('scripts/sql/create/create_faction.sql'));
		$db->exec(file_get_contents('scripts/sql/create/create_player.sql'));
	} else if($argv[2] == 'populate_db'){
		//Populate Entity & Player
		$dir = new DirectoryIterator(dirname($argv[1].'server-database/*'));
		foreach($dir as $fileinfo){
			$fExt = $fileinfo->getExtension();
			$fName = $fileinfo->getFilename();
			if($fExt=="ent"){
				if(strpos($fName, 'ENTITY_') !== false && strpos($fName,'ENTITY_PLAYER') === false){
					addSQLEnt($fName, $db);
				} else if(strpos($fName, 'ENTITY_PLAYERSTATE_') !== false){
					addSQLPlayer($fName, $db);
				}
			}
		}
		addSQLFaction($db);
	} else if($argv[2] == 'update_db'){
		//Update Entity & Player
		$entUID = array();
		$playerName = array();
		$dir = new DirectoryIterator(dirname($argv[1].'server-database/*'));
		foreach($dir as $fileinfo){
			$fExt = $fileinfo->getExtension();
			$fName = $fileinfo->getFilename();
			if($fExt=="ent"){
				if(strpos($fName, 'ENTITY_') !== false && strpos($fName,'ENTITY_PLAYER') === false){
					$UID = str_replace('.ent', '', $fName);
					array_push($entUID, $UID);
				} else if(strpos($fName, 'ENTITY_PLAYERSTATE_') !== false){
					$player = str_replace('ENTITY_PLAYERSTATE_', '',str_replace('.ent', '', $fName));
					array_push($playerName, $player);
				}
			}
		}
		sort($entUID);
		sort($playerName);
		updateSQLEnt($entUID, $db);
		updateSQLPlayer($playerName, $db);
		updateSQLFaction($db);
	} {
		echo "Valide arguments are: create_db, populate_db or update_db\n";
		die();
	}
	
	function addSQLEnt($file, $db){
		global $SMDecoder;
		global $argv;
		$ent = $SMDecoder->decodeSMFile($argv[1]."server-database/".$file);
			
		$sPos = $ent['sPos']['x'].','.$ent['sPos']['y'].','.$ent['sPos']['z'];
		$transformX = $ent['transformX']['x'].','.$ent['transformX']['y'].','.$ent['transformX']['z'];
		$transformY = $ent['transformY']['x'].','.$ent['transformY']['y'].','.$ent['transformY']['z'];
		$transformZ = $ent['transformZ']['x'].','.$ent['transformZ']['y'].','.$ent['transformZ']['z'];
		$LocalPos = $ent['LocalPos']['x'].','.$ent['LocalPos']['y'].','.$ent['LocalPos']['z'];
		$Dim = $ent['DIM'][0].','.$ent['DIM'][1].','.$ent['DIM'][2].','.$ent['DIM'][3].','.$ent['DIM'][4].','.$ent['DIM'][5];
			
		$request = $db->prepare("INSERT INTO Entity (UID, Type, Name, FactionID, Creator, Last_Mod, mass, pw, sh, sPos, transformX, transformY, transformZ, LocalPos, DIM, Gen_ID) VALUES (:UID, :Type, :Name, :FactionID, :Creator, :Last_Mod, :mass, :pw, :sh, :sPos, :transformX, :transformY, :transformZ, :LocalPos, :DIM, :Gen_ID)");
		$request->bindParam(':UID', $ent['UID']);
		$request->bindParam(':Type', $ent['Type']);
		$request->bindParam(':Name', $ent['Name']);
		$request->bindParam(':FactionID', $ent['FactionID']);
		$request->bindParam(':Creator', $ent['Creator']);
		$request->bindParam(':Last_Mod', $ent['Last_Mod']);
		$request->bindParam(':mass', $ent['mass']);
		$request->bindParam(':pw', $ent['pw']);
		$request->bindParam(':sh', $ent['sh']);
		$request->bindParam(':sPos', $sPos);
		$request->bindParam(':transformX', $transformX);
		$request->bindParam(':transformY', $transformY);
		$request->bindParam(':transformZ', $transformZ);
		$request->bindParam(':LocalPos', $LocalPos);
		$request->bindParam(':DIM', $Dim);
		$request->bindParam(':Gen_ID', $ent['Gen_ID']);
		$request->execute();
	}
	
	function addSQLPlayer($file, $db) {
		
		global $SMDecoder;
		global $argv;
		
		$ent = $SMDecoder->decodeSMFile($argv[1] . "server-database/" . $file);
		
		$sPos = $ent['Sector']['x'] . ',' . $ent['Sector']['y'] . ',' . $ent['Sector']['z'];
		
		$request = $db->prepare("INSERT INTO Player (Name, Credits, Sector, FactionID) VALUES (:Name, :Credits, :Sector, :FactionID)");
		$request->bindParam(':Name', $ent['Name']);
		$request->bindParam(':Credits', $ent['Credits']);
		$request->bindParam(':Sector', $sPos);
		$request->bindParam(':FactionID', $ent['FactionID']);
		$request->execute();
		
	}
		
	function addSQLFaction($db) {
		
		global $SMDecoder;
		global $argv;
		
		$ent = $SMDecoder->decodeSMFile($argv[1]."server-database/FACTIONS.fac");
		
		for($i = 0; $i < count($ent); $i++){
			
			$request = $db->prepare("INSERT INTO Faction (ID, UID, Name, Home, Rank0, Rank1, Rank2, Rank3, Rank4) VALUES (:ID, :UID, :Name, :Home, :Rank0, :Rank1, :Rank2, :Rank3, :Rank4)");
			$request->bindParam(':ID', $ent[$i]['ID']);
			$request->bindParam(':UID', $ent[$i]['UID']);
			$request->bindParam(':Name', $ent[$i]['Name']);
			$request->bindParam(':Home', $ent[$i]['Home']);
			$request->bindParam(':Rank0', $ent[$i]['Ranks'][0]);
			$request->bindParam(':Rank1', $ent[$i]['Ranks'][1]);
			$request->bindParam(':Rank2', $ent[$i]['Ranks'][2]);
			$request->bindParam(':Rank3', $ent[$i]['Ranks'][3]);
			$request->bindParam(':Rank4', $ent[$i]['Ranks'][4]);
			$request->execute();
			
			for($x = 0; $x < count($ent[$i]['Member']); $x++){
				$name = $ent[$i]['Member'][$x]['Name'];
				$rank = $ent[$i]['Member'][$x]['Rank'];
				$request = $db->prepare("UPDATE Player SET FactionRank='" . $rank . "' WHERE Name='" . $name . "'");
				$request->execute();
			}
		}
	}
	
	function updateSQLEnt($arr, $db){
		$request = $db->query("SELECT * FROM Entity");
		$data = $request->fetchall();
		$diff = array();
		for($i = 0; $i < count($data); $i++){
			array_push($diff, $data[$i]['UID']);
		}
		$toRemove = array_diff($diff, $arr);
		$toAdd = array_diff($arr, $diff);
		$toUpdate = array_diff($arr, $toAdd);
		foreach($toRemove as $key => $val){
			$request = $db->query('DELETE FROM Entity WHERE UID="'.$val.'";');
			$request->execute();
		}
		foreach($toAdd as $key => $val){
			$file = $val.".ent";
			addSQLEnt($file, $db);
		}
		
		foreach($toUpdate as $key => $val){
			global $SMDecoder;
			global $argv;
			$file = $val.".ent";
			$ent = $SMDecoder->decodeSMFile($argv[1]."server-database/".$file);
			
			$sPos = $ent['sPos']['x'].','.$ent['sPos']['y'].','.$ent['sPos']['z'];
			$transformX = $ent['transformX']['x'].','.$ent['transformX']['y'].','.$ent['transformX']['z'];
			$transformY = $ent['transformY']['x'].','.$ent['transformY']['y'].','.$ent['transformY']['z'];
			$transformZ = $ent['transformZ']['x'].','.$ent['transformZ']['y'].','.$ent['transformZ']['z'];
			$LocalPos = $ent['LocalPos']['x'].','.$ent['LocalPos']['y'].','.$ent['LocalPos']['z'];
			$Dim = $ent['DIM'][0].','.$ent['DIM'][1].','.$ent['DIM'][2].','.$ent['DIM'][3].','.$ent['DIM'][4].','.$ent['DIM'][5];
				
			$request = $db->prepare("UPDATE Entity SET Type='".$ent['Type']."' WHERE UID='".$val."';");
			$request->execute();
			$request = $db->prepare("UPDATE Entity SET Name='".$ent['Name']."' WHERE UID='".$val."';");
			$request->execute();
			$request = $db->prepare("UPDATE Entity SET FactionID='".$ent['FactionID']."' WHERE UID='".$val."';");
			$request->execute();
			$request = $db->prepare("UPDATE Entity SET Creator='".$ent['Creator']."' WHERE UID='".$val."';");
			$request->execute();
			$request = $db->prepare("UPDATE Entity SET Last_Mod='".$ent['Last_Mod']."' WHERE UID='".$val."';");
			$request->execute();
			$request = $db->prepare("UPDATE Entity SET mass='".$ent['mass']."' WHERE UID='".$val."';");
			$request->execute();
			$request = $db->prepare("UPDATE Entity SET pw='".$ent['pw']."' WHERE UID='".$val."';");
			$request->execute();
			$request = $db->prepare("UPDATE Entity SET sh='".$ent['sh']."' WHERE UID='".$val."';");
			$request->execute();
			$request = $db->prepare("UPDATE Entity SET sPos='".$ent['sPos']."' WHERE UID='".$val."';");
			$request->execute();
			$request = $db->prepare("UPDATE Entity SET transformX='".$ent['transformX']."' WHERE UID='".$val."';");
			$request->execute();
			$request = $db->prepare("UPDATE Entity SET transformY='".$ent['transformY']."' WHERE UID='".$val."';");
			$request->execute();
			$request = $db->prepare("UPDATE Entity SET transformZ='".$ent['transformZ']."' WHERE UID='".$val."';");
			$request->execute();
			$request = $db->prepare("UPDATE Entity SET LocalPos='".$ent['LocalPos']."' WHERE UID='".$val."';");
			$request->execute();
			$request = $db->prepare("UPDATE Entity SET DIM='".$ent['DIM']."' WHERE UID='".$val."';");
			$request->execute();
			$request = $db->prepare("UPDATE Entity SET Gen_ID='".$ent['Gen_ID']."' WHERE UID='".$val."';");
			$request->execute();
		}
	}
	
	function updateSQLPlayer($arr, $db){
		global $SMDecoder;
		global $argv;
		$request = $db->query("SELECT * FROM Player");
		$data = $request->fetchall();
		$diff = array();
		for($i = 0; $i < count($data); $i++){
			array_push($diff, $data[$i]['UID']);
		}
		$toRemove = array_diff($diff, $arr);
		$toAdd = array_diff($arr, $diff);
		$toUpdate = array_diff($arr, $toAdd);
		foreach($toRemove as $key => $val){
			$request = $db->query('DELETE FROM Player WHERE Name="'.$val.'";');
			$request->execute();
		}
		foreach($toAdd as $key => $val){
			$file = 'ENTITY_PLAYERSTATE_'.$val.".ent";
			addSQLPlayer($file, $db);
		}
		foreach($toUpdate as $key => $val){
			$file = $val.".ent";
			$ent = $SMDecoder->decodeSMFile($argv[1]."server-database/".$file);
			$sPos = $ent['Sector']['x'].','.$ent['Sector']['y'].','.$ent['Sector']['z'];
			$request = $db->prepare("UPDATE Entity SET Credits='".$ent['Credits']."' WHERE Name='".$val."';");
			$request->execute();
			$request = $db->prepare("UPDATE Entity SET Sector='".$sPos."' WHERE Name='".$val."';");
			$request->execute();
			$request = $db->prepare("UPDATE Entity SET FactionID='".$ent['FactionID']."' WHERE Name='".$val."';");
			$request->execute();
		}
	}
		
	function updateSQLFaction($db){
		global $SMDecoder;
		global $argv;
		$fac = $SMDecoder->decodeSMFile($argv[1]."server-database/FACTIONS.fac");
		$request = $db->query("SELECT * FROM Faction");
		$data = $request->fetchall();
		$diff_file = array();
		$diff_sql = array();
		for($i = 0; $i < count($fac); $i++){
			array_push($diff_file, $fac[$i]['UID']);
		}
		for($i = 0; $i < count($data); $i++){
			array_push($diff_sql, $data[$i]['UID']);
		}
		$toRemove = array_diff($diff_sql, $diff_file);
		$toAdd = array_diff($diff_file, $diff_sql);
		$toUpdate = array_diff($diff_file, $toAdd);
		
		foreach($toRemove as $key => $val){
			$request = $db->query('DELETE FROM Faction WHERE UID="'.$val.'";');
			$request->execute();
		}
		foreach($toAdd as $key => $val){
			for($i = 0; $i < count($fac); $i++){
				if($fac[$i]['UID'] == $val){
					$request = $db->prepare("INSERT INTO Faction (ID, UID, Name, Home, Rank0, Rank1, Rank2, Rank3, Rank4) VALUES (:ID, :UID, :Name, :Home, :Rank0, :Rank1, :Rank2, :Rank3, :Rank4)");
					$request->bindParam(':ID', $fac[$i]['ID']);
					$request->bindParam(':UID', $fac[$i]['UID']);
					$request->bindParam(':Name', $fac[$i]['Name']);
					$request->bindParam(':Home', $fac[$i]['Home']);
					$request->bindParam(':Rank0', $fac[$i]['Ranks'][0]);
					$request->bindParam(':Rank1', $fac[$i]['Ranks'][1]);
					$request->bindParam(':Rank2', $fac[$i]['Ranks'][2]);
					$request->bindParam(':Rank3', $fac[$i]['Ranks'][3]);
					$request->bindParam(':Rank4', $fac[$i]['Ranks'][4]);
					$request->execute();
					
					for($x = 0; $x < count($ent[$i]['Member']); $x++){
						$name = $fac[$i]['Member'][$x]['Name'];
						$rank = $fac[$i]['Member'][$x]['Rank'];
						$request = $db->prepare("UPDATE Player SET FactionRank='".$rank."' WHERE Name='".$name."'");
						$request->execute();
					}
				}
			}
			
		}
		foreach($toUpdate as $key => $val){
			for($i = 0; $i < count($fac); $i++){
				if($fac[$i]['UID'] == $val){
					$request = $db->prepare("UPDATE Faction SET ID='".$fac[$i]['ID']."' WHERE UID='".$val."'");
					$request->execute();
					$request = $db->prepare("UPDATE Faction SET Name='".$fac[$i]['Name']."' WHERE UID='".$val."'");
					$request->execute();
					$request = $db->prepare("UPDATE Faction SET Home='".$fac[$i]['Home']."' WHERE UID='".$val."'");
					$request->execute();
					$request = $db->prepare("UPDATE Faction SET Rank0='".$fac[$i]['Ranks'][0]."' WHERE UID='".$val."'");
					$request->execute();
					$request = $db->prepare("UPDATE Faction SET Rank1='".$fac[$i]['Ranks'][1]."' WHERE UID='".$val."'");
					$request->execute();
					$request = $db->prepare("UPDATE Faction SET Rank2='".$fac[$i]['Ranks'][2]."' WHERE UID='".$val."'");
					$request->execute();
					$request = $db->prepare("UPDATE Faction SET Rank3='".$fac[$i]['Ranks'][3]."' WHERE UID='".$val."'");
					$request->execute();
					$request = $db->prepare("UPDATE Faction SET Rank4='".$fac[$i]['Ranks'][4]."' WHERE UID='".$val."'");
					$request->execute();
					
					for($x = 0; $x < count($fac[$i]['Member']); $x++){
						$name = $fac[$i]['Member'][$x]['Name'];
						$rank = $fac[$i]['Member'][$x]['Rank'];
						$request = $db->prepare("UPDATE Player SET FactionRank='".$rank."' WHERE Name='".$name."'");
						$request->execute();
					}
				}
			}
		}
		
	}
?>