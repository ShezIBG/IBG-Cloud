import { Location } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

declare var Mangler: any;

@Component({
	selector: 'app-stock-smoothpower-install',
	templateUrl: './stock-smoothpower-install.component.html'
})
export class StockSmoothpowerInstallComponent implements OnInit, OnDestroy {

	private sub: any;

	details;
	siList;
	disabled = false;

	data = {
		id: null,
		hg_id: null,
		hg_name: '',
		hg_list: null,
		client_id: null,
		client_name: '',
		client_list: null,
		building_id: null,
		building_name: '',
		building_list: null,
		floor_id: null,
		floor_name: '',
		floor_list: null,
		area_id: null,
		area_name: '',
		area_list: null
	};

	get hgId() { return this.data.hg_id; };
	get clientId() { return this.data.client_id; };
	get buildingId() { return this.data.building_id; };
	get floorId() { return this.data.floor_id; };
	get areaId() { return this.data.area_id; };

	set hgId(value) { this.data.hg_id = value; this.refreshClients(); }
	set clientId(value) { this.data.client_id = value; this.refreshBuildings(); }
	set buildingId(value) { this.data.building_id = value; this.refreshFloors(); }
	set floorId(value) { this.data.floor_id = value; this.refreshAreas(); }
	set areaId(value) { this.data.area_id = value; }

	get hgList() { return this.data.hg_list; }
	get clientList() { return this.data.client_list; }
	get buildingList() { return this.data.building_list; }
	get floorList() { return this.data.floor_list; }
	get areaList() { return this.data.area_list; }

	set hgList(value) {
		this.data.hg_list = value;
		if (this.hgId !== null && this.hgId !== 'new') {
			if (!Mangler.findOne(value, { id: this.hgId })) this.hgId = null;
		}
	}

	set clientList(value) {
		this.data.client_list = value;
		if (this.clientId !== null && this.clientId !== 'new') {
			if (!Mangler.findOne(value, { id: this.clientId })) this.clientId = null;
		}
	}

	set buildingList(value) {
		this.data.building_list = value;
		if (this.buildingId !== null && this.buildingId !== 'new') {
			if (!Mangler.findOne(value, { id: this.buildingId })) this.buildingId = null;
		}
	}

	set floorList(value) {
		this.data.floor_list = value;
		if (this.floorId !== null && this.floorId !== 'new') {
			if (!Mangler.findOne(value, { id: this.floorId })) this.floorId = null;
		}
	}

	set areaList(value) {
		this.data.area_list = value;
		if (this.areaId !== null && this.areaId !== 'new') {
			if (!Mangler.findOne(value, { id: this.areaId })) this.areaId = null;
		}
	}

	constructor(
		private app: AppService,
		private api: ApiService,
		private route: ActivatedRoute,
		private location: Location
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			this.data.id = params['id'];
			this.details = null;

			this.api.smoothpower.getSmoothPowerUnit(this.data.id, response => {
				const crumbs = response.data.breadcrumbs;
				crumbs[crumbs.length - 1].description = 'Install ' + crumbs[crumbs.length - 1].description;

				this.app.header.clearAll();
				this.app.header.addCrumbs(crumbs);
				this.details = response.data.details || {};
				this.siList = response.data.si_list;

				this.refreshHoldingGroups();
				this.refreshClients();
			}, response => {
				this.app.notifications.showDanger(response.message);
			});
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

	goBack() {
		this.location.back();
	}

	refreshHoldingGroups() {
		this.api.settings.listHoldingGroups('SI', this.details.system_integrator_id, response => {
			this.hgList = response.data.list;
		}, response => {
			this.hgList = [];
			this.app.notifications.showDanger(response.message);
		});
	}

	refreshClients() {
		if (this.data.hg_id === 'new') {
			this.clientList = [];
		} else if (this.data.hg_id === null) {
			this.api.settings.listClients('SI', this.details.system_integrator_id, response => {
				this.clientList = response.data.list;
			}, response => {
				this.clientList = [];
				this.app.notifications.showDanger(response.message);
			});
		} else {
			this.api.settings.listClients('HG', this.data.hg_id, response => {
				this.clientList = response.data.list;
			}, response => {
				this.clientList = [];
				this.app.notifications.showDanger(response.message);
			});
		}
	}

	refreshBuildings() {
		if (this.data.client_id === null || this.data.client_id === 'new') {
			this.buildingList = [];
		} else {
			this.api.settings.listBuildings('C', this.data.client_id, response => {
				this.buildingList = response.data.list;
			}, response => {
				this.buildingList = [];
				this.app.notifications.showDanger(response.message);
			});
		}
	}

	refreshFloors() {
		if (this.data.building_id === null || this.data.building_id === 'new') {
			this.floorList = [];
		} else {
			this.api.settings.listBuildingFloors(this.data.building_id, response => {
				this.floorList = response.data.list;
			}, response => {
				this.floorList = [];
				this.app.notifications.showDanger(response.message);
			});
		}
	}

	refreshAreas() {
		if (this.data.floor_id === null || this.data.floor_id === 'new') {
			this.areaList = [];
		} else {
			this.api.settings.listFloorAreas(this.data.floor_id, response => {
				this.areaList = response.data.list;
			}, response => {
				this.areaList = [];
				this.app.notifications.showDanger(response.message);
			});
		}
	}

	install() {
		this.disabled = true;
		this.api.smoothpower.installUnit(this.data, () => {
			this.disabled = false;
			this.goBack();
			this.app.notifications.showSuccess('SmoothPower unit installed.');
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

}
