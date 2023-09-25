import { ApiService } from './../../api.service';
import { Component, OnInit, Input } from '@angular/core';

@Component({
	selector: 'app-settings-sites',
	templateUrl: './settings-sites.component.html'
})
export class SettingsSitesComponent implements OnInit {

	@Input() level;
	@Input() levelId;

	list: any = [];
	count = { sites: 0 };
	systemIntegratorAdmin = false;
	configuratorBase = '';
	search = '';

	constructor(private api: ApiService) { }

	ngOnInit() {
		const success = response => {
			this.list = response.data.list || [];
			this.systemIntegratorAdmin = !!response.data.system_integrator_admin;
			this.configuratorBase = response.data.configurator_base_url;
		};

		if (this.level) {
			this.api.settings.listBuildings(this.level, this.levelId, success);
		} else {
			this.api.settings.listAllBuildings(success);
		}
	}

	openConfigurator(id) {
		window.open(this.configuratorBase + id);
	}

}
