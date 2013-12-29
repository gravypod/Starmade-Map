/*
   Product: StarOS Map
   Description: This script generate a 3D Starmap for starmade
   License: http://creativecommons.org/licenses/by/3.0/legalcode

   Version: 0.1								Date: 2013-12-28
   By Blackcancer
  
   website: 
   support: blackcancer@initsysrev.net
*/

var StarOS_Map = function(options){
	this.settings = options || {};
	if(!this.settings.parentId){
		alert("CCP Starmap: You must specify the parentElementId parameter.");
	} 
	else {
		StarOS_Map.prototype.init = function(){
			this.DEFAULT_WIDTH	= 800;
			this.DEFAULT_HEIGHT = 600;
			this.DEFAULT_FS_KEY = "f";
			this.DEFAULT_SPAWN  = {
				x: 2,
				y: 2,
				z: 2
			};
			this.DEFAULT_SHOW_SHIP = false;

			this.entityDictionary = new Object;
			this.factionDictionary = new Object;
			this.mouseMove = new Object;
			this.sectorSize = 1300;
			this.intersected = false;
			this.container = $('#' + this.settings.parentId);
			this.showInfo = false;

			this.stageWidth  = parseInt(this.settings.width || this.DEFAULT_WIDTH);
			this.stageHeight = parseInt(this.settings.height || this.DEFAULT_HEIGHT);
			this.stageFsKey = this.settings.FsKey || this.DEFAULT_FS_KEY;
			this.stageShowShip = this.settings.showShip || this.DEFAULT_SHOW_SHIP;

			this.DEFAULT_VIEW = {
				ASPECT: this.stageWidth / this.stageHeight,
				ANGLE: 	45,
				NEAR:	0.1,
				FAR: 	4100000
			};

			if(this.settings.view != undefined){
				this.stageView = {
					aspect: parseInt(this.settings.view.aspect || this.DEFAULT_VIEW.ASPECT),
					angle:  parseInt(this.settings.view.angle  || this.DEFAULT_VIEW.ANGLE),
					near:   parseInt(this.settings.view.near   || this.DEFAULT_VIEW.NEAR),
					far:    parseInt(this.settings.view.far    || this.DEFAULT_VIEW.FAR)
				}
			} else {
				this.stageView = {
					aspect: this.DEFAULT_VIEW.ASPECT,
					angle:  this.DEFAULT_VIEW.ANGLE,
					near:   this.DEFAULT_VIEW.NEAR,
					far:    this.DEFAULT_VIEW.FAR
				}
			}

			if(this.settings.spawn != undefined){
				this.stageSpawn = {
					x: parseInt(this.settings.spawn.x || this.DEFAULT_SPAWN.x),
					y: parseInt(this.settings.spawn.y || this.DEFAULT_SPAWN.y),
					z: parseInt(this.settings.spawn.z || this.DEFAULT_SPAWN.z)
				}
			} else {
				this.stageSpawn = {
					x: this.DEFAULT_SPAWN.x,
					y: this.DEFAULT_SPAWN.y,
					z: this.DEFAULT_SPAWN.z
				}
			}

			this.initWebGL();
			this.setupEvent();

			this.animate = this.animate.bind(this);
			this.animate();
		},

		StarOS_Map.prototype.initWebGL = function(){
			this.keyboard = new THREEx.KeyboardState();
			this.projector = new THREE.Projector();
			this.scene = new THREE.Scene();
			this.camera = new THREE.PerspectiveCamera(
				this.stageView.angle,
				this.stageView.aspect,
				this.stageView.neat,
				this.stageView.far
			);
			if(Detector.webgl){
				this.renderer = new THREE.WebGLRenderer({
					antialias: true
				});
			} else {
				this.renderer = new THREE.CanvasRenderer();
			}

			this.scene.add(this.camera);
			this.camera.position.set(this.stageSpawn.x, this.stageSpawn.y, 10000);
			this.camera.lookAt(this.stageSpawn);

			this.renderer.setSize(this.stageWidth, this.stageHeight);
			this.renderer.domElement.id = "StarmapRenderer";
			this.container.append(this.renderer.domElement);
			this.container.width(this.stageWidth);
			this.container.height(this.stageHeight);

			this.initSkybox();
			this.initEntity();
			this.initFaction();
		},

		StarOS_Map.prototype.initSkybox = function(){
			var imagePrefix = "res/img/starmap/skybox/generic_",
			 	directions  = ["posx", "negx", "posy", "negy", "posz", "negz"],
			    imageSuffix = ".png",
				geometry = new THREE.CubeGeometry(this.stageView.far, this.stageView.far, this.stageView.far),   
				materialArray = [];
				
			for (var i = 0; i < 6; i++)
				materialArray.push(new THREE.MeshBasicMaterial({
					map: THREE.ImageUtils.loadTexture(imagePrefix + directions[i] + imageSuffix),
					side: THREE.BackSide
				}));
			var material = new THREE.MeshFaceMaterial( materialArray ),
				skyBox = new THREE.Mesh(geometry, material);
			this.scene.add(skyBox);
		},

		StarOS_Map.prototype.initEntity = function(){
			var StarMap = this;
			jqxhrEntity = $.ajax({
				url:	  'entities.json',
				type:	  'GET',
				dataType: 'json'
			})
			.done(function(json){
				if(!StarMap.stageShowShip){
					var i = json.length;
					while(i--){
					   if(json[i].type === "5" ){
						   json.splice(i,1);
					   }
					}
				}
				for(i = 0; i < json.length; i++){
					sPos = json[i].sPos.split(',');
					localPos = json[i].localPos.split(',');
					entity = new StarmapEntity();
					entity.creator	= json[i].creator;
					entity.fid		= parseInt(json[i].fid);
					entity.genId	= parseInt(json[i].genID);
					entity.lastMod	= json[i].lastMod;
					entity.mass		= parseFloat(json[i].mass);
					entity.name		= json[i].name;
					entity.position.x = StarMap.sectorSize * parseInt(sPos[0]) + parseInt(localPos[0]);
					entity.position.y = StarMap.sectorSize * parseInt(sPos[1]) + parseInt(localPos[1]);
					entity.position.z = StarMap.sectorSize * parseInt(sPos[2]) + parseInt(localPos[2]);
					entity.power	= parseFloat(json[i].pw);
					entity.sector.x	= parseInt(sPos[0]);
					entity.sector.y	= parseInt(sPos[1]);
					entity.sector.z	= parseInt(sPos[2]);
					entity.shield	= parseFloat(json[i].sh);
					entity.type		= parseInt(json[i].type);
					entity.uid		= json[i].UID;
					
					entity.init();
					entity.generate(StarMap.camera, StarMap.scene);
					StarMap.entityDictionary[entity.uid] = entity;
				}
			})
			.fail(function(result, err_code, err){console.debug("Ajax error: " + err);})
			.always(function(){});
		},
		
		StarOS_Map.prototype.initFaction = function(){
			var StarMap = this;
			jqxhrFaction = $.ajax({
				url: 'factions.json',
				type: 'GET',
				dataType: 'json'
			})
			.done(function(json){
				for(i = 0; i < json.length; i++){
					StarMap.factionDictionary[json[i].ID] = {
						id: json[i].ID,
						uid: json[i].UID,
						name: json[i].name,
						home: json[i].home,
						ranks:{
							r0: json[i].r0,
							r1: json[i].r1,
							r2: json[i].r2,
							r3: json[i].r3,
							r4: json[i].r4
						}
					};
				};
			})
			.fail(function(result, err_code, err){console.debug("Ajax error: " + err);})
			.always(function(){});
		},

		StarOS_Map.prototype.setupEvent = function(){
			// EVENTS
			THREEx.WindowResize(this.renderer, this.camera);
			THREEx.FullScreen.bindKey({
				charCode: this.stageFsKey.charCodeAt(0)
			});
			
			//CONTROLS
			this.controls = new THREE.OrbitControls(this.camera, this.renderer.domElement);
			
			//STATS
			this.stats = new Stats();
			this.stats.domElement.style.position = 'absolute';
			this.stats.domElement.style.top = this.container.offset().top + 'px';
			this.stats.domElement.style.left = (this.container.width() + this.container.offset().left - 80) + 'px';
			this.stats.domElement.style.zIndex = 100;
			this.container.append(this.stats.domElement);	
			
			//EVENT LISTENER
			document.addEventListener( 'mousemove', this.onMouseMove.bind(this), false );
			document.addEventListener( 'mousedown', this.onMouseDown.bind(this), false );
			$button = $('#mapInfoButton');
			$button.css('top', $("#StarmapRenderer").offset().top + 'px');
			$('#mapInfo').css('top', $("#StarmapRenderer").offset().top + $button.height() + 'px');
			$button.click(function(){
				$('#mapInfo').toggle();
				if(this.showInfo){
					$('#mapInfoButton').css('background-image', 'url(../../../../res/img/starmap/buttonUnroll.png)');
					this.showInfo = false;
				} else {
					$('#mapInfoButton').css('background-image', 'url(../../../../res/img/starmap/buttonRoll.png)');
					this.showInfo = true;
				}
			}).bind(this);
		},

		StarOS_Map.prototype.onMouseMove = function(event){
			event.preventDefault();
			
			$canvas = $('#StarmapRenderer');
			this.mouseMove.x =  ((event.clientX - $canvas.offset().left) / $canvas.width())  * 2 - 1;
			this.mouseMove.y = -((event.clientY - $canvas.offset().top) / $canvas.height()) * 2 + 1;
		},

		StarOS_Map.prototype.onMouseDown = function(event){
			event.preventDefault();
			
			var vector = new THREE.Vector3(this.mouseMove.x, this.mouseMove.y, 0.5);
			this.projector.unprojectVector(vector, this.camera);
			var ray = new THREE.Raycaster(this.camera.position, vector.sub(this.camera.position).normalize());
			var intersects = ray.intersectObjects(this.scene.children)
			
			if(intersects.length > 0 && intersects[0].object.uid){
				var uid = intersects[0].object.uid;
				this.EntityMapInfo(this.entityDictionary[uid]);
			}
		},

		StarOS_Map.prototype.EntityMapInfo = function(entity){
			var	facName = "None",
				isHomeworld = false;
			
			for(i in this.factionDictionary){
				if(entity.fid == this.factionDictionary[i].id){
					facName = this.factionDictionary[i].name;
					isHomeworld = (entity.uid == this.factionDictionary[i].home) ? true : false;
				}
			}
			
			$parent = $('#mapInfo');
			$parent.empty();
			
			$img = $('<img id="mapInfPic" class="mapInfo"/>');
			$img.attr('src', entity.texture);
			$img.attr('width', entity.scale[0]);
			$img.attr('height', entity.scale[1]);
			
			$name = $('<h4 id="mapInfName" class="mapInfo"/>');
			$name.text(entity.name);
			margin = ($parent.width() - textWidth($name.text())) / 2;
			$name.css('left', margin - 10 + 'px');
			
			$type = $('<label id="mapInfType" class="mapInfo"/>');
			$type.text("Type: " + entity.typeLabel);
			
			$pos = $('<label id="mapInfPos" class="mapInfo"/>');
			$pos.text("Sector: " + entity.sector.x + ", " + entity.sector.y + ", " + entity.sector.z);
			
			$fac = $('<label id="mapInfFac" class="mapInfo"/>');
			isHomeworld ? $fac.text("Faction: " + facName + "'s Homeworld") : $fac.text("Faction: " + facName);
			
			$mass = $('<label id="mapInfMass" class="mapInfo"/>');
			$mass.text("Mass: " + entity.mass);
			
			$pow = $('<label id="mapInfPow" class="mapInfo"/>');
			$pow.text("Max power: " + entity.power);
			
			shieldBlocks = getShieldBlocks(entity.shield);
			rechargeRate = getShieldRate(shieldBlocks);
			
			$sh = $('<label id="mapInfSh" class="mapInfo"/>');
			$sh.text("Max shield: " + entity.shield);
			$shRate = $('<label id="mapInfShRate" class="mapInfo"/>');
			$shRate.text("Shield recharge: " + rechargeRate + " s/sec");
			
			$parent.append($img);
			$parent.append($name);
			$parent.append($type);
			$parent.append($pos);
			$parent.append($fac);
			$parent.append($mass);
			$parent.append($pow);
			$parent.append($sh);
			$parent.append($shRate);
			
			$parent.show();
		},

		StarOS_Map.prototype.animate = function(){
  			requestAnimationFrame(StarOS_Map.prototype.animate.bind(this));
			this.render();		
			this.update();
		},

		StarOS_Map.prototype.update = function(){
			var vector = new THREE.Vector3(this.mouseMove.x, this.mouseMove.y, 0.5);
			this.projector.unprojectVector(vector, this.camera);
			var ray = new THREE.Raycaster(this.camera.position, vector.sub(this.camera.position).normalize());
			var intersects = ray.intersectObjects(this.scene.children)
			
			if(intersects.length > 0){
				// if the closest object intersected is not the currently stored intersection object
				if(intersects[0].object != this.intersected){
					if (this.intersected) 
						this.intersected.material.color.setHex(this.intersected.currentHex);
					this.intersected = intersects[0].object;
					this.intersected.currentHex = this.intersected.material.color.getHex();
					this.intersected.material.color.setHex(0xffff00);
				}
			} else {
				if(this.intersected)
					this.intersected.material.color.setHex(this.intersected.currentHex);
				this.intersected = null;
			}
			
			if(!THREEx.FullScreen.activated()){
				this.renderer.setSize(this.stageWidth, this.stageHeight);
			}
			
			if (this.keyboard.pressed("space")){
				this.camera.position.set(this.stageSpawn.x, this.stageSpawn.y, 1000);
				this.camera.lookAt(this.stageSpawn);
			}
			
			this.controls.update();
			this.stats.update();
		},

		StarOS_Map.prototype.render = function(){
			this.renderer.render( this.scene, this.camera );
		},

		this.init();
	}
};

getShieldBlocks = function(shield){
	power = 2/3;
	return Math.round(Math.pow(shield / 350 , 1 /power)) / 3.5;
};

getShieldRate = function(blocks){
	return Math.floor(Math.pow(blocks * 5, 0.5) * 50);
};
	
textWidth = function(text){
	var calc = '<span style="display:none">' + text + '</span>';
	$('body').append(calc);
	var width = $('body').find('span:last').width();
	$('body').find('span:last').remove();
	return width;
};