import { ApiService } from './../../api.service';
import { Component, OnInit, Input } from '@angular/core';

@Component({
	selector: 'app-settings-system-integrators',
	templateUrl: './settings-system-integrators.component.html'
})
export class SettingsSystemIntegratorsComponent implements OnInit {

	@Input() level;
	@Input() levelId;

	list: any = [];
	count = { si: 0 };
	search = '';

	constructor(private api: ApiService) { }

	ngOnInit() {
		const success = response => {
			this.list = response.data.list || [];
		};

		if (this.level) {
			this.api.settings.listSystemIntegrators(this.level, this.levelId, success);
		} else {
			this.api.settings.listAllSystemIntegrators(success);
		}
	}

}
