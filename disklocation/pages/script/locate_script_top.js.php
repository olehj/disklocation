function locateStart(locateDisk){
	if(locateDisk) {
		//console.log("Locating started: " + locateDisk.id);
		locateDisk.removeEventListener("click", locateStart);
		locateDisk.addEventListener("click", locateStop);
		locateDisk.value = "Stop";
		locateDisk.style.backgroundColor = '#000000';
		var diskpath = encodeURI(locateDisk.id);
		$.get('<?php echo $_GET["path"] ?>/pages/locate.php',{ disklocation:diskpath, cmd:"start"},function(data) {
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
	var diskpath = encodeURI(locateDisk.id);
	$.get('<?php echo $_GET["path"] ?>/pages/locate.php',{ disklocation:diskpath, cmd:"stop"},function(data) {
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
	
	$.get('<?php echo $_GET["path"] ?>/pages/locate.php',{ cmd:"killall"},function(data) {
		// script is handled in the background, nothing to do here
	});
}
