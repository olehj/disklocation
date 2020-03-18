//  Copyright 2019-2020, Ole-Henrik Jakobsen
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
