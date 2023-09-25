import { ModuleComponent } from './../../shared/module/module.component';
import { AppService } from './../../app.service';
import { ApiService } from './../../api.service';
import { Component, OnInit } from '@angular/core';

@Component({
	selector: 'app-settings',
	template: `<router-outlet></router-outlet>`
})
export class SettingsComponent extends ModuleComponent implements OnInit {

	constructor(
		public app: AppService,
		private api: ApiService
	) {
		super(app);
	}

	ngOnInit() {
		this.api.settings.getNavigation(response => {
			this.app.sidebar.setMenu(response.data);
		});
	}

}
