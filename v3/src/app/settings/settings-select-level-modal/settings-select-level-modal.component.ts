import { ApiService, PermissionLevels } from './../../api.service';
import { ModalComponent } from './../../shared/modal/modal.component';
import { Component, OnInit, ViewChild } from '@angular/core';

@Component({
	selector: 'app-settings-select-level-modal',
	templateUrl: './settings-select-level-modal.component.html'
})
export class SettingsSelectLevelModalComponent implements OnInit {

	@ViewChild(ModalComponent) modal: ModalComponent;

	tabs: any[] = [];
	selectedTab = null;
	buttons: any[] = ['0|Cancel'];

	list: any[] = [];
	count = { list: 0 };
	search = '';

	selectedBuildingId = null;
	areaTab = null;

	constructor(private api: ApiService) { }

	ngOnInit() {
		this.api.settings.getSelectLevels(response => {
			response.data.forEach(level => {
				switch (level) {
					case PermissionLevels.ETICOM:
						this.buttons.unshift('1|*Add Eticom level');
						this.buttons = this.buttons.slice();
						break;

					case PermissionLevels.SERVICE_PROVIDER:
					case PermissionLevels.SYSTEM_INTEGRATOR:
					case PermissionLevels.HOLDING_GROUP:
					case PermissionLevels.CLIENT:
					case PermissionLevels.BUILDING:
						this.tabs.push({ id: level, description: PermissionLevels.getPluralDescription(level) });
						break;
				}
			});

			if (this.tabs.length) this.selectTab(this.tabs[0].id);
		});
	}

	selectTab(id) {
		this.selectedTab = id;
		this.list = [];

		const success = response => {
			if (this.selectedTab === id) this.list = response.data.list || [];
		};

		switch (id) {
			case PermissionLevels.SERVICE_PROVIDER: this.api.settings.listAllServiceProviders(success); break;
			case PermissionLevels.SYSTEM_INTEGRATOR: this.api.settings.listAllSystemIntegrators(success); break;
			case PermissionLevels.HOLDING_GROUP: this.api.settings.listAllHoldingGroups(success); break;
			case PermissionLevels.CLIENT: this.api.settings.listAllClients(success); break;
			case PermissionLevels.BUILDING: this.api.settings.listAllBuildings(success); break;
			case PermissionLevels.AREA: this.api.settings.listAreas(PermissionLevels.BUILDING, this.selectedBuildingId, success); break;
		};
	}

	showAreas(building) {
		this.selectedBuildingId = building.id;
		if (this.areaTab) {
			this.areaTab.description = building.description;
		} else {
			this.areaTab = { id: PermissionLevels.AREA, description: building.description };
			this.tabs.push(this.areaTab);
		}
		this.selectTab(PermissionLevels.AREA);
	}

	selectItem(level, id) {
		this.modal.close([level, id]);
	}

	modalHandler(event) {
		this.modal.close(event.data && event.data.id === 1 ? [PermissionLevels.ETICOM, 0] : null);
	}

}
