<?php    
    session_start();
    header('Content-Type: text/html; charset=UTF-8');
	include '../configs/StarOS_Config.php';
	
    try
    {
        $db = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASSWORD);
        $db->exec("SET CHARACTER SET utf8");
    }
    catch (Exception $e)
    {
        $jsonErr = array('title'=>'MySQL error:', 'err' =>$e->getMessage());
        die(json_encode($jsonErr));
    }
	
	if(isset($_GET['getSQL'])){
		if($_GET['getSQL'] == 'entity'){
            $table = 'Entity';
			$request = $db->query('SELECT * FROM '.$table);
			$json = array();
            while ($data = $request->fetch()){
				array_push($json, array(
					'UID' => $data['UID'],
					'type' => $data['Type'],
					'name' => $data['Name'],
					'fid' => $data['FactionID'],
					'creator' => $data['Creator'],
					'lastMod' => $data['Last_Mod'],
					'mass' => $data['mass'],
					'pw' => $data['pw'],
					'sh' => $data['sh'],
					'sPos' => $data['sPos'],
					'localPos' => $data['LocalPos'],
					'genID' => $data['Gen_ID']
				));
			}
            echo json_encode($json);
		} 
		else if($_GET['getSQL'] == 'faction'){
			$table = 'Faction';
			$request = $db->query('SELECT * FROM '.$table);
			$json = array();
            while ($data = $request->fetch()){
				array_push($json, array(
					'ID' => $data['ID'],
					'UID' => $data['UID'],
					'name' => $data['Name'],
					'home' => $data['Home'],
					'r0' => $data['Rank0'],
					'r1' => $data['Rank1'],
					'r2' => $data['Rank2'],
					'r3' => $data['Rank3'],
					'r4' => $data['Rank4']
				));
			}
            echo json_encode($json);
		}
	}
	
	$request->closeCursor();
?> 