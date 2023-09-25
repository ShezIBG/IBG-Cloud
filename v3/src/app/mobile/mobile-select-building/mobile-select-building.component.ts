import { Component, OnInit } from '@angular/core';
import { ApiService } from 'app/api.service';
import { AppService } from 'app/app.service';
import { MobileService } from '../mobile.service';

@Component({
	selector: 'app-mobile-select-building',
	templateUrl: './mobile-select-building.component.html',
	styleUrls: ['./mobile-select-building.component.less']
})
export class MobileSelectBuildingComponent implements OnInit {

	list = null;

	constructor(
		public app: AppService,
		private api: ApiService,
		public mobile: MobileService
	) { }

	ngOnInit() {
		this.list = null;
		this.api.mobile.listBuildings(response => {
			this.list = response.data;
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

}
