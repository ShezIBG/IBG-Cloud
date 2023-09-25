import { ApiService } from './../../api.service';
import { Component, OnInit } from '@angular/core';

@Component({
	selector: 'app-settings-service-providers',
	templateUrl: './settings-service-providers.component.html'
})
export class SettingsServiceProvidersComponent implements OnInit {

	list: any = [];
	count = { sp: 0 };
	search = '';

	constructor(private api: ApiService) { }

	ngOnInit() {
		this.api.settings.listAllServiceProviders(response => {
			this.list = response.data.list || [];
		});
	}

}
