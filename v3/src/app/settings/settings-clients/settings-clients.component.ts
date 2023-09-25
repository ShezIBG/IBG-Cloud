import { ApiService } from './../../api.service';
import { Component, OnInit, Input } from '@angular/core';

@Component({
	selector: 'app-settings-clients',
	templateUrl: './settings-clients.component.html'
})
export class SettingsClientsComponent implements OnInit {

	@Input() level;
	@Input() levelId;

	list: any = [];
	count = { clients: 0 };
	systemIntegratorAdmin = false;
	search = '';

	constructor(private api: ApiService) { }

	ngOnInit() {
		const success = response => {
			this.list = response.data.list || [];
			this.systemIntegratorAdmin = !!response.data.system_integrator_admin;
		};

		if (this.level) {
			this.api.settings.listClients(this.level, this.levelId, success);
		} else {
			this.api.settings.listAllClients(success);
		}
	}

}
