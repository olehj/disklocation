<?php header('Content-Type: text/javascript'); ?>
//  Copyright 2019-2024, Ole-Henrik Jakobsen
//
//  This file is part of Disk Location for Unraid.
//
//  Disk Location for Unraid is free software: you can redistribute it and/or modify
//  it under the terms of the GNU General Public License as published by
//  the Free Software Foundation, either version 3 of the License, or
//  (at your option) any later version.
//
//  Disk Location for Unraid is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
//
//  You should have received a copy of the GNU General Public License
//  along with Disk Location for Unraid.  If not, see <https://www.gnu.org/licenses/>.

<?php
	$path = filter_var($_GET["path"], FILTER_SANITIZE_URL);
	
	if(!file_exists($_SERVER['DOCUMENT_ROOT'] . $path . "/pages/locate.php")) {
		die("<h1>Path: " . $_SERVER['DOCUMENT_ROOT'] . $path . "/pages/locate.php does not exist.</h1>");
	}
	else {
		$path = $_GET["path"] . "/pages/locate.php";
	}
?>

function locateStart(locateDisk){
	if(locateDisk) {
		//console.log("Locating started: " + locateDisk.id);
		locateDisk.removeEventListener("click", locateStart);
		locateDisk.addEventListener("click", locateStop);
		locateDisk.value = "Stop";
		locateDisk.style.backgroundColor = '#000000';
		document.getElementById("bg1-" + locateDisk.id).classList.add('flex-container-locate');
		document.getElementById("bg2-" + locateDisk.id).classList.add('flex-container-locate');
		document.getElementById("bg3-" + locateDisk.id).classList.add('flex-container-locate');
		document.getElementById("bg4-" + locateDisk.id).classList.add('flex-container-locate');
		var diskpath = encodeURI(locateDisk.id);
		$.get('<?php echo $path ?>',{ disklocation:diskpath, cmd:"start"},function(data) {
			// script is handled in the background, nothing to do here
		});
	}
}

function locateStop(locateDisk){
	//console.log("Locating stopped: " + locateDisk.id);
	locateDisk.removeEventListener("click", locateStop);
	locateDisk.addEventListener("click", locateStart);
	locateDisk.value = "Locate";
	locateDisk.style.backgroundColor = '#FFFFFF';
	document.getElementById("bg1-" + locateDisk.id).classList.remove('flex-container-locate');
	document.getElementById("bg2-" + locateDisk.id).classList.remove('flex-container-locate');
	document.getElementById("bg3-" + locateDisk.id).classList.remove('flex-container-locate');
	document.getElementById("bg4-" + locateDisk.id).classList.remove('flex-container-locate');
	var diskpath = encodeURI(locateDisk.id);
	$.get('<?php echo $path ?>',{ disklocation:diskpath, cmd:"stop"},function(data) {
		// script is handled in the background, nothing to do here
	});
}

function locateKillAll(locateDisk){
	var y = document.getElementsByClassName(locateDisk);
	var i;
	for (i = 0; i < y.length; i++) {
		y[i].removeEventListener("click", locateStop);
		y[i].addEventListener("click", locateStart);
		y[i].value = "Locate";
		y[i].style.backgroundColor = '#FFFFFF';
		//console.log("Locating killed: " + y[i].id);
	}
	
	$.get('<?php echo $path ?>',{ cmd:"killall"},function(data) {
		// script is handled in the background, nothing to do here
	});
}
