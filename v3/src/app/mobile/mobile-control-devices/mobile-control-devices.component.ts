import { Component, NgModuleRef, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { ApiService } from 'app/api.service';
import { AppService } from 'app/app.service';
import { ControlDeviceDetailsModalComponent } from 'app/control/control-device-details-modal/control-device-details-modal.component';
import { KnxValue } from 'app/control/knx-value';
import { MobileService } from '../mobile.service';

declare var Mangler: any;

@Component({
	selector: 'app-mobile-control-devices',
	templateUrl: './mobile-control-devices.component.html',
	styleUrls: ['./mobile-control-devices.component.less']
})
export class MobileControlDevicesComponent implements OnInit {

	data = null;
	areas = [];
	floorplanIndex: any = {};
	selected = null;
	selectedFP = null;
	selectedArea = null;
	timer;
	timerDelay = 30000;
	timerSchedule = [];
	destroyed;
	lastRequest = 0;
	knx = {};

	disabled = false;

	$panzoom = null;
	scale = 1;

	private sub: any;

	constructor(
		private app: AppService,
		private api: ApiService,
		private route: ActivatedRoute,
		private moduleRef: NgModuleRef<any>,
		public mobile: MobileService,
		private router: Router
	) { }

	ngOnInit() {
		this.destroyed = false;
		this.sub = this.route.params.subscribe(params => {
			this.mobile.buildingId = params['buildingId'];
			this.reloadData();
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
		this.destroyed = true;
		clearTimeout(this.timer);
	}

	scheduleReloadBurst() {
		this.lastRequest += 1;
		this.timerSchedule = [4000, 1000, 1000, 2000, 3000, 4000, 5000, 6000, 7000, 8000, 9000, 10000, 10000, 10000, 30000];
		this.scheduleReload();
	}

	scheduleReload() {
		clearTimeout(this.timer);
		if (this.timerSchedule.length) this.timerDelay = this.timerSchedule.shift();
		this.timer = setTimeout(() => this.reloadData(), this.timerDelay);
	}

	reloadData(burst = false) {
		const thisRequest = ++this.lastRequest;

		clearTimeout(this.timer);

		this.api.control.listDevices(this.mobile.buildingId, response => {
			if (this.destroyed) return;
			if (this.lastRequest !== thisRequest) return;

			this.data = response.data;

			this.processData();

			if (burst) {
				this.scheduleReloadBurst();
			} else {
				this.scheduleReload();
			}
		}, response => {
			this.router.navigate(['../..'], { replaceUrl: true });
			// if (this.destroyed) return;
			// if (this.lastRequest !== thisRequest) return;

			// this.app.notifications.showDanger(response.message);
			// if (burst) {
			// 	this.scheduleReloadBurst();
			// } else {
			// 	this.scheduleReload();
			// }
		});
	}

	processData() {
		this.areas = this.data.areas;
		delete this.data.areas;

		this.floorplanIndex = Mangler.index(this.data.floorplans, 'id');

		this.areas.forEach(area => {
			area.devices = [];
		});

		const deviceIndex = Mangler.index(this.data.devices, 'id');
		const areaIndex = Mangler.index(this.areas, 'id');

		this.data.devices.forEach(device => {
			device.floorplan = null;
			device.floorplan_item = null;

			if (device.area_id) {
				const area = areaIndex[device.area_id];
				if (area) area.devices.push(device);
			}
		});

		this.data.floorplan_items.forEach(item => {
			const device = deviceIndex[item.item_id];
			if (device) {
				device.floorplan_item = item;
				device.floorplan = this.floorplanIndex[item.floorplan_id];
			}
		});

		// Filter out empty areas
		this.areas = this.areas.filter(area => {
			return !!area.devices.length;
		});

		if (!this.selected && this.areas.length > 0) {
			this.selectDevice(deviceIndex[this.areas[0].devices[0].id]);
		}

		// Create KNX value objects
		this.knx = {};
		this.data.knx.forEach(item => {
			this.knx[item.id] = new KnxValue(item.id, item.knx_datatype, item.knx_subtype, item.value, item.is_readonly);
		});
		this.data.knxIndex = Mangler.index(this.data.knx, 'id');
	}

	getDeviceKnx(device) {
		return Mangler.find(this.data.knx, { item_id: device.id });
	}

	selectDevice(device) {
		if (this.selected === device.id) return;

		const oldFP = this.selectedFP;
		this.selectedFP = device.floorplan;
		this.selectedArea = device.area_id;
		this.selected = device.id;
	}

	refreshScale() {
		this.scale = this.$panzoom.panzoom('instance').scale;
	}

	deviceDetails(device) {
		const modalSub = this.app.modal.modalService.modalClosed.subscribe(modalData => {
			modalSub.unsubscribe();
			this.reloadData(true);
		});

		this.app.modal.open(ControlDeviceDetailsModalComponent, this.moduleRef, { building_id: this.mobile.buildingId, id: device });
	}

	updateKnxValue(knxValue: KnxValue) {
		if (!knxValue) return;

		this.api.control.sendKnxValues({
			building_id: this.mobile.buildingId,
			knx: [
				{ id: knxValue.id, datatype: knxValue.dataType, value: knxValue.value }
			]
		}, () => {
			this.scheduleReloadBurst();
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

}
