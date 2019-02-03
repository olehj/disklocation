function sleep(ms) {
	return new Promise(resolve => setTimeout(resolve, ms));
}
$('.diskLocation').click(async function(e) {
	var locateDisk = document.getElementById(this.id);
	
	if(locateDisk.value == "Locate") {
		locateKillAll(locateDisk.className);
		await sleep(200);
		locateStart(locateDisk);
	}
	else {
		locateStop(locateDisk);
		await sleep(200);
		locateKillAll(locateDisk.className);
	}
});
