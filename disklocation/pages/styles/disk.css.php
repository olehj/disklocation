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
