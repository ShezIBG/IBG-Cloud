import { Component, Input, OnInit } from '@angular/core';
import { ApiService } from 'app/api.service';
import { AppService } from 'app/app.service';

@Component({
	selector: 'app-settings-contracts',
	templateUrl: './settings-contracts.component.html'
})
export class SettingsContractsComponent implements OnInit {

	@Input() level;
	@Input() levelId;

	data: any = null;

	constructor(
		private api: ApiService,
		private app: AppService
	) { }

	ngOnInit() {
		this.refresh();
	}

	refresh() {
		this.api.settings.listContractTemplates(this.level, this.levelId, response => {
			this.data = response.data;
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

}
