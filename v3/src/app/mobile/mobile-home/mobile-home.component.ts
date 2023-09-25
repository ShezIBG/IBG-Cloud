import { Component, OnInit } from '@angular/core';
import { ApiService } from 'app/api.service';
import { AppService } from 'app/app.service';
import { MobileService } from '../mobile.service';

@Component({
	selector: 'app-mobile-home',
	template: ``
})
export class MobileHomeComponent implements OnInit {

	constructor(
		public app: AppService,
		private mobile: MobileService,
		private api: ApiService
	) { }

	ngOnInit() {
		this.api.mobile.getDefaultBuilding(response => {
			this.mobile.moduleName = '';
			this.mobile.selectBuilding(response.data);
		}, response => {
			// No buildings found with mobile-compatible modules, go to desktop site
			this.app.redirect(this.app.getAppURL());
		});
	}

}
