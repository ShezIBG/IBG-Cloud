import { RelayDeviceDetailsModalComponent } from './../relay-device-details-modal/relay-device-details-modal.component';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy, NgModuleRef } from '@angular/core';

declare var Mangler: any;

@Component({
	selector: 'app-relay-building',
	templateUrl: './relay-building.component.html',
	styleUrls: ['../relay.module.less']
})
export class RelayBuildingComponent implements OnInit, OnDestroy {

	private sub: any;

	id: number;
	data: any = null;
	areas = [];
	first = true;
	destroyed;
	timer;

	selectedDevice;

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
		this.destroyed = true;
		this.sub.unsubscribe();
	}

	getAddress(b: any) {
		const result = ['<b>' + b.description + '</b>'];
		if (b.address) {
			('' + b.address).split(',').forEach(s => result.push(s.trim()));
		}
		if (b.posttown) result.push(b.posttown);
		if (b.postcode) result.push(b.postcode);

		return result.join('<br>');
	}

	reloadData() {
		clearTimeout(this.timer);
		if (this.destroyed) return;

		this.api.relay.getBuilding(this.id, res => {
			if (this.destroyed) return;
			this.data = res.data;

			if (this.first) {
				this.first = false;
				this.app.header.clearCrumbs();
				if (this.data && this.data.building_count > 1) this.app.header.addCrumb({ description: 'Buildings', route: '/relay' });
				this.app.header.addCrumb({ description: this.data.building.description, compact: true });
			}
		});

		this.api.relay.listDevices(this.id, response => {
			if (this.destroyed) return;
			const deviceData = response.data;

			this.areas = deviceData.areas;
			delete deviceData.areas;

			this.areas.forEach(area => {
				area.devices = [];
			});

			const deviceIndex = Mangler.index(deviceData.devices, 'id');
			const areaIndex = Mangler.index(this.areas, 'id');

			deviceData.devices.forEach(device => {
				if (device.area_id) {
					const area = areaIndex[device.area_id];
					if (area) area.devices.push(device);
				}
			});

			// Filter out empty areas
			this.areas = this.areas.filter(area => {
				return !!area.devices.length;
			});

			if (this.selectedDevice) {
				this.selectedDevice = deviceIndex[this.selectedDevice.id];
			} else {
				if (this.areas.length) this.selectedDevice = deviceIndex[this.areas[0].devices[0].id];
			}

			this.timer = setTimeout(() => this.reloadData(), 30000);
		}, () => {
			if (this.destroyed) return;
			this.timer = setTimeout(() => this.reloadData(), 30000);
		});
	}

	deviceDetails(device) {
		this.selectedDevice = device;

		const modalSub = this.app.modal.modalService.modalClosed.subscribe(() => {
			modalSub.unsubscribe();
			this.reloadData();
		});

		this.app.modal.open(RelayDeviceDetailsModalComponent, this.moduleRef, device.id);
	}

}
