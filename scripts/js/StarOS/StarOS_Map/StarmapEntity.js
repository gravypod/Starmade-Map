/*
   Product: StarOS Map entity
   Description: This script id the class object for entity
   License: http://creativecommons.org/licenses/by/3.0/legalcode

   Version: 0.1								Date: 2013-12-28
   By Blackcancer
  
   website: 
   support: blackcancer@initsysrev.net
*/

var StarmapEntity = function(){
	this.creator = "unknown",
	this.fid = 0,
	this.genId = 0,
	this.lastMod = "",
	this.mass = 0.0,
	this.name = "undef",
	this.position = {
		x: 0,
		y: 0,
		z: 0
	},
	this.power = 0,
	this.sector ={
		x: 0,
		y: 0,
		z: 0
	},
	this.shield = 0.0,
	this.type = 0,
	this.uid = undefined;
	this.scale = [120, 120, 1.0];
	this.planeScale = this.scale;
	
	StarmapEntity.prototype.init = function(){
		switch(this.type){
			case 1:
				this.name = "Shop";
				this.texture = "res/img/starmap/shop.png";
				this.planeScale = [64, 120, 1.0];
				this.typeLabel = "Shop";
				break;
			case 2:
				this.texture = "res/img/starmap/station.png";
				this.typeLabel = "Station";
				break;
			case 3:
				this.name = "Asteroid";
				this.scale = [64, 64, 1.0]
				this.planeScale = this.scale;
				this.texture = "res/img/starmap/asteroid.png";
				this.typeLabel = "Asteroid";
				break;
			case 4:
				switch(this.genId){
					case 0:
						this.texture = "res/img/starmap/redPlanet.png";
						this.typeLabel = "Red planet";
						break;
					case 1:
						this.texture = "res/img/starmap/terranPlanet.png";
						this.typeLabel = "Terran planet";
						break;
					case 2:
						this.texture = "res/img/starmap/desertPlanet.png";
						this.typeLabel = "Desert planet";
						break;
					case 3:
						this.texture = "res/img/starmap/alienPlanet.png";
						this.typeLabel = "Alien planet";
						break;
					case 4:
						this.texture = "res/img/starmap/icePlanet.png";
				this.typeLabel = "Ice planet";
						break;
					default:
						this.texture = "res/img/starmap/fallPlanet.png";
						this.typeLabel = "Unknown planet";
						break;
				};
				break;
			case 5:
				this.texture = "res/img/starmap/ship.png";
				this.typeLabel = "Ship";
				break;
			default:
				break;
		};
	},
	
	StarmapEntity.prototype.generate = function(camera, scene){
		var geometry = new THREE.PlaneGeometry(this.planeScale[0], this.planeScale[1], 1, 1),
			plane	 = new THREE.Mesh(geometry),
			texture	 = new THREE.ImageUtils.loadTexture(this.texture),
			material = new THREE.SpriteMaterial({
				map: texture,
				useScreenCoordinates: false
			});
			if(this.fid == -1){
				material.setValues({color: 0xff0000});
			}
		var	sprite	 = new THREE.Sprite(material);
			
			this.position.x = this.position.x / 4.7;
			this.position.y = this.position.y / 4.7;
			this.position.z = this.position.z / 4.7;
			plane.position = sprite.position = this.position;
			plane.quaternion = camera.quaternion;
			plane.uid = sprite.uid = this.uid;
			plane.visible = false;
			sprite.scale.set(this.scale[0], this.scale[1], this.scale[2]);
			
			scene.add(plane);
			scene.add(sprite);
	}
}