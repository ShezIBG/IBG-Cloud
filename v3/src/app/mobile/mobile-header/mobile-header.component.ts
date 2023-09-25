import { Location } from '@angular/common';
import { Component } from '@angular/core';
import { AppService } from 'app/app.service';
import { MobileService } from '../mobile.service';

@Component({
	selector: 'app-mobile-header',
	templateUrl: './mobile-header.component.html',
	styleUrls: ['./mobile-header.component.less']
})
export class MobileHeaderComponent {

	constructor(
		public app: AppService,
		public location: Location,
		private mobile: MobileService
	) { }

	homeRoute() {
		if (this.mobile.buildingId) return ['/mobile', this.mobile.buildingId];
		return ['/mobile'];
	}

}
