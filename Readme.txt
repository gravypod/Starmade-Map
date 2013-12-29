/*
   Product: StarOS_Map
   Description: This script generate a 3D Starmap for starmade
   License: http://creativecommons.org/licenses/by/3.0/legalcode

   Version: 0.1								Date: 2014-12-28
   By Blackcancer
  
   website: initsysrev.net
   support: blackcancer@initsysrev.net
   
   ps: sorry for my poor english ;)
*/

==> CHANGELOG <==
	0.1: - Get information relative to an entity when you click on it
		 - Show starmade entity in 3D space 
		 - Generate database from starmade file

==> SETUP <==
	1.copy all file in your site without index.html
	2.Add all javascript in index.html header to your page
	3.In the balise <body> , add this
 
		<div id"ID_OF_THE_STARMAP_DIV"></div>
		<script type="text/javascript">
		<!--
			$(document).ready(function(){
				starmap = new StarOS_Map({
					parentId: 'ID_OF_THE_STARMAP_DIV',
				});
			});
		-->
		</script>
	
	4. go to scripts/php/configs folder and edit StarOS_Config.php to match with your config.
	5. In shell, type:
		"php setup.php DIRECTORY/OF/YOUR/GAME create_db".
		"php setup.php DIRECTORY/OF/YOUR/GAME populate_db".
	6. Update your map with command "php setup.php DIRECTORY/OF/YOUR/GAME update_db".
	
==> DOCUMENTATION <==
	StarOS_Map can have differents argument:
		parentId: (string),
		width: (int),		//default 800
		height: (int),		//default 600
		spawn:{
			x: (int),		//default 2
			y: (int),		//default 2
			z: (int)		//default 2
		},
		showShip: bool,		//default false
		FsKey: (string),	//default "f"
		view:{
			aspect: (int),  //default width / height
			angle: (int),	//default 45
			near: (int),	//default 0.1
			far: (int)		//default 4100000
		}
		
		each arguments are optional except parentId. 
		"width and height" define the size used by canvas.
		"spawn" is the default point where camera looking.
		"showShip" set true to show ship in starmap.
		"FsKey" is the default key used for switch to fullscreen, only char and number are allowed.
		"view" this options are relative to three.js camera, use only if you now what you are doing
