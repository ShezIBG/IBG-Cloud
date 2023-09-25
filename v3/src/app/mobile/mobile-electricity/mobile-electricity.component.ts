import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from 'app/api.service';
import { AppService } from 'app/app.service';
import { Subscription } from 'rxjs';
import { MobileService } from '../mobile.service';

@Component({
	selector: 'app-mobile-electricity',
	templateUrl: './mobile-electricity.component.html',
	styleUrls: ['./mobile-electricity.component.less']
})
export class MobileElectricityComponent implements OnInit {

	data;
	selectedTimePeriod = 'yesterday';
	expandedCategoryId = null;

	private routeSubscription: Subscription;

	constructor(
		public app: AppService,
		private api: ApiService,
		private route: ActivatedRoute,
		private mobile: MobileService
	) { }

	ngOnInit() {
		this.routeSubscription = this.route.params.subscribe(params => {
			this.mobile.buildingId = params['buildingId'];
			this.reload();
		});
	}

	reload() {
		this.api.mobile.electricityInfo(this.mobile.buildingId, this.selectedTimePeriod, response => {
			this.data = response.data;
			this.selectedTimePeriod = this.data.selected_time_period;
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

}
