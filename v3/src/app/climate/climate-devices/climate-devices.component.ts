import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, ViewChild, OnDestroy, NgModuleRef } from '@angular/core';
import { ClimateDeviceDetailsModalComponent } from '../climate-device-details-modal/climate-device-details-modal.component';

declare var Mangler: any;
declare var $: any;

@Component({
	selector: 'app-climate-devices',
	templateUrl: './climate-devices.component.html',
	styleUrls: ['./climate-devices.component.less']
})
export class ClimateDevicesComponent implements OnInit, OnDestroy {

	@ViewChild('plan') plan;
	@ViewChild('zoomin') zoomin;
	@ViewChild('zoomout') zoomout;

	id: number;
	data = null;
	areas = [];
	floorplanIndex: any = {};
	selected = null;
	selectedFP = null;
	selectedArea = null;
	openDevice = null;
	holdFor = 240;
	timer;
	destroyed;

	disabled = false;

	$panzoom = null;
	scale = 1;

	modeOptions = [
		{ id: null, desc: '' },
		{ id: 'cool', desc: 'Cool' },
		{ id: 'heat', desc: 'Heat' },
		{ id: 'auto', desc: 'Auto' },
		{ id: 'dry', desc: 'Dry' },
		{ id: 'haux', desc: 'Haux' },
		{ id: 'fan', desc: 'Fan' }
	];

	fanOptions = [
		{ id: null, desc: '' },
		{ id: 'very_low', desc: 'Very low' },
		{ id: 'low', desc: 'Low' },
		{ id: 'medium', desc: 'Medium' },
		{ id: 'high', desc: 'High' },
		{ id: 'top', desc: 'Top' },
		{ id: 'auto', desc: 'Auto' }
	];

	louvreOptions = [
		{ id: null, desc: 'Not set' },
		{ id: 'vertical', desc: 'Vertical' },
		{ id: '30', desc: '30&deg;' },
		{ id: '45', desc: '45&deg;' },
		{ id: '60', desc: '60&deg;' },
		{ id: 'horizontal', desc: 'Horizontal' },
		{ id: 'auto', desc: 'Auto' },
		{ id: 'off', desc: 'Off' }
	];

	private sub: any;

	constructor(
		private app: AppService,
		private api: ApiService,
		private route: ActivatedRoute,
		private moduleRef: NgModuleRef<any>
	) { }

	ngOnInit() {
		this.destroyed = false;
		this.sub = this.route.params.subscribe(params => {
			this.id = params['id'];
			this.reloadData();
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
		this.destroyed = true;
		clearTimeout(this.timer);
	}

	reloadData(keepDevice = false) {
		clearTimeout(this.timer);

		this.api.climate.listDevices(this.id, response => {
			if (this.destroyed) return;
			this.data = response.data;

			this.app.header.clearCrumbs();
			if (this.data && this.data.building_count > 1) this.app.header.addCrumb({ description: 'Buildings', route: '/climate' });
			this.app.header.addCrumb({ description: this.data.building.description, route: '/climate/building/' + this.data.building.id });
			this.app.header.addCrumb({ description: 'Devices', compact: true });

			this.processData(keepDevice);
			setTimeout(() => this.initPanzoom(), 0);

			this.timer = setTimeout(() => this.reloadData(true), 30000);
		}, response => {
			if (this.destroyed) return;
			this.app.notifications.showDanger(response.message);
			this.timer = setTimeout(() => this.reloadData(true), 30000);
		});
	}

	processData(keepDevice = false) {
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

			device.category_icon = (device.category || '').substr(0, 1).toUpperCase() || 'A';

			if (device.area_id) {
				const area = areaIndex[device.area_id];
				area.devices.push(device);
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
		} else {
			if (!keepDevice) this.reloadDevice();
		}
	}

	selectDevice(device) {
		if (this.selected === device.id) return;

		const oldFP = this.selectedFP;
		this.selectedFP = device.floorplan;
		this.selectedArea = device.area_id;
		this.selected = device.id;

		if (this.selectedFP && (!oldFP || this.selectedFP.id !== oldFP.id)) {
			setTimeout(() => this.initPanzoom(), 0);
		}

		this.reloadDevice();
	}

	reloadDevice() {
		if (!this.selected) return;

		const deviceId = this.selected;
		this.api.climate.getDevice(deviceId, response => {
			this.disabled = false;
			if (deviceId === this.selected) {
				this.openDevice = response.data;
				this.openDevice.info.category_icon = (this.openDevice.info.category || '').substr(0, 1).toUpperCase() || 'A';
				this.openDevice.record.ac_onoff = !!this.openDevice.record.ac_onoff;
				this.openDevice.hold.ac_onoff = !!this.openDevice.hold.ac_onoff;
				this.addSetPoint(0);
			}
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

	refreshScale() {
		this.scale = this.$panzoom.panzoom('instance').scale;
	}

	initPanzoom() {
		if (!this.plan) return;

		const context = this;

		this.$panzoom = $(this.plan.nativeElement).panzoom({
			onZoom: (e, panzoom) => {
				this.refreshScale();
			},
			$zoomIn: $(this.zoomin.nativeElement),
			$zoomOut: $(this.zoomout.nativeElement)
		});

		this.$panzoom.parent().off('mousewheel.focal').on('mousewheel.focal', e => {
			e.preventDefault();
			const delta = e.delta || e.originalEvent.wheelDelta;
			const zoomOut = delta ? delta < 0 : e.originalEvent.deltaY > 0;

			// Pan has to be enabled for focal zoom to work
			const originalPan = this.$panzoom.panzoom('option', 'disablePan');
			this.$panzoom.panzoom('option', 'disablePan', false);

			context.$panzoom.panzoom('zoom', zoomOut, {
				increment: 0.1,
				animate: false,
				focal: e
			});

			this.$panzoom.panzoom('option', 'disablePan', originalPan);
		});

		this.refreshScale();
	}

	addSetPoint(d) {
		let newSetpoint = Math.floor(this.openDevice.hold.ac_setpoint + d);
		const minSetpoint = this.openDevice.record.min_setpoint;
		const maxSetpoint = this.openDevice.record.max_setpoint;

		if (minSetpoint && newSetpoint < minSetpoint) newSetpoint = minSetpoint;
		if (maxSetpoint && newSetpoint > maxSetpoint) newSetpoint = maxSetpoint;

		this.openDevice.hold.ac_setpoint = newSetpoint;
	}

	setHold() {
		if (!this.openDevice) return;

		this.openDevice.hold.minutes = this.holdFor;
		this.disabled = true;
		this.api.climate.setHold(this.openDevice.hold, () => {
			this.app.notifications.showSuccess('Temporary hold set.');
			this.reloadDevice();
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

	removeHold() {
		if (!this.openDevice) return;

		this.disabled = true;
		this.api.climate.removeHold(this.openDevice.record.id, () => {
			this.app.notifications.showSuccess('Temporary hold removed.');
			this.reloadDevice();
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

	deviceDetails() {
		const modalSub = this.app.modal.modalService.modalClosed.subscribe(() => {
			modalSub.unsubscribe();
			this.reloadData();
		});

		this.app.modal.open(ClimateDeviceDetailsModalComponent, this.moduleRef, this.selected);
	}

}
