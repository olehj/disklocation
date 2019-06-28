input.diskLocation {
	padding: 5px;
	width: 70px;
	height: 30px;
	background-color: #F2F2F2;
	margin-top: -20px;
	margin-bottom: -20px;
	margin-left: auto;
	margin-right: auto;
}

.grid-container {
	display: grid;
	justify-content: center;
	grid-gap: 0;
}
.grid-container>div {
	display: grid;
	grid-gap: 0;
}

.flex-container_h, .flex-container_v {
	display: flex;
	margin: 0;
	flex-direction: column;
	justify-content: flex-start;
}

.flex-container_h>div {
	display: flex;
	/*width: <?php echo $tray_width[$gid] ?>px;*/
	/*height: <?php echo $tray_height[$gid] ?>px;*/
	margin: 5px;
	padding: 10px 10px 10px 10px;
	justify-content: space-between;
	border: 2px solid #000000;
	border-radius: 5px;
}
.flex-container_v>div {
	display: flex;
	/*width: <?php echo $tray_height[$gid] ?>px;*/
	/*height: <?php echo $tray_width[$gid] ?>px;*/
	margin: 5px;
	padding: 10px 10px 10px 10px;
	flex-direction: column;
	border: 2px solid #000000;
	border-radius: 5px;
}

.flex-container-start {
	min-height: 25px;
	text-align: center;
}
.flex-container-start>div {
	display: flex;
}

.flex-container-middle_h {
	width: 100%;
	padding-left: 10px;
}
.flex-container-middle_v {
	width: 100%;
	padding: 10px 0 20px 0;
	writing-mode: vertical-rl;
	text-orientation: mixed;
	text-align: left;
	margin-bottom: auto;
}
.flex-container-middle_h>div, .flex-container-middle_v>div {
	display: flex;
	text-align: left;
}

.flex-container-end {
	display: flex;
	text-align: right;
}

.flex-container-layout_h, .flex-container-layout_v {
	display: flex;
	margin: 0;
	flex-direction: column;
	justify-content: flex-start;
}
.flex-container-layout_h>div {
	display: flex;
	/*width: <?php print($tray_width[$gid]/10); ?>px;*/
	/*height: <?php print($tray_height[$gid]/10); ?>px;*/
	margin: 1px;
	padding: 5px 5px 5px 5px;
	justify-content: center;
	border: 1px solid #000000;
	border-radius: 1px;
	align-items: center;
}
.flex-container-layout_v>div {
	display: flex;
	/*width: <?php print($tray_height[$gid]/10); ?>px;*/
	/*height: <?php print($tray_width[$gid]/10); ?>px;*/
	margin: 1px;
	padding: 5px 5px 5px 5px;
	flex-direction: column;
	border: 1px solid #000000;
	border-radius: 1px;
	align-items: center;
	justify-content: center;
}


/*inactive*/
foofoofoo.diskLocation_foo {
	padding: 5px;
	width: 70px;
	height: 30px;
	background-color: #F2F2F2;
	margin-top: auto;
	margin-bottom: auto;
	margin-left: -20px;
	margin-right: -20px;
	transform: rotate(90deg);
}
fooofoofoo.grid-container_foo {
	/*grid-template-columns: <?php echo $grid_columns_styles ?>;*/
	/*grid-template-rows: <?php echo $grid_rows_styles ?>;*/
	/*grid-auto-flow: <?php echo $grid_count ?>;*/ /* column: bays ordered from top to bottom from left to right | row: ..from left to right from top to bottom */
}
