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
	margin: 5px;
	padding: 10px 10px 10px 10px;
	justify-content: space-between;
	border: 2px solid #000000;
	border-radius: 5px;
}
.flex-container_v>div {
	display: flex;
	margin: 5px;
	padding: 10px 10px 10px 10px;
	flex-direction: column;
	border: 2px solid #000000;
	border-radius: 5px;
}

.flex-container-start {
	min-height: 0;
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
	text-orientation: sideways;
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
	margin: 1px;
	padding: 5px 5px 5px 5px;
	justify-content: center;
	border: 1px solid #000000;
	border-radius: 1px;
	align-items: center;
}
.flex-container-layout_v>div {
	display: flex;
	margin: 1px;
	padding: 5px 5px 5px 5px;
	flex-direction: column;
	border: 1px solid #000000;
	border-radius: 1px;
	align-items: center;
	justify-content: center;
}
.flex-container-locate {
	animation: locate-disklocation-blue 1s linear infinite;
}
@keyframes locate-disklocation-blue {
	0% {background-color: #CCCCCC;}
	50% {background-color: #0066FF;}
	100% {background-color: #CCCCCC;}
}

/* Mobile-friendly tray map.
 *
 * Each tray tile is rendered with fixed pixel dimensions (see $tray_width/$tray_height
 * in devices.php), and groups of trays are floated side by side. That's fine on a
 * desktop screen, but on a phone-width viewport several floated groups side by side
 * either overflow the page horizontally or get squashed/overlapped. Rather than
 * rewriting the tray sizing to be fluid (which would need every tile's inline
 * width/height recalculated server-side), we make each group's own tray grid
 * independently scrollable and stack groups vertically, so every tray map is always
 * fully visible and swipeable at its native, correctly-proportioned size.
 */
@media (max-width: 900px) {
	.dl-group-wrap {
		float: none !important;
		display: block;
		max-width: 100%;
		margin-left: auto;
		margin-right: auto;
	}
	.dl-group-wrap .grid-container {
		display: inline-grid;
		max-width: 100%;
		overflow-x: auto;
		-webkit-overflow-scrolling: touch;
		padding-bottom: 10px; /* keep the scrollbar from sitting on top of the bottom row of trays */
	}
}
