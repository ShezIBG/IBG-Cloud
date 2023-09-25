import { Component, OnDestroy, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from 'app/api.service';
import { AppService } from 'app/app.service';
import { Subscription } from 'rxjs';
import { MobileService } from '../mobile.service';

@Component({
	selector: 'app-mobile-building-home',
	templateUrl: './mobile-building-home.component.html',
	styleUrls: ['./mobile-building-home.component.less']
})
export class MobileBuildingHomeComponent implements OnInit, OnDestroy {

	moduleList;

	private routeSubscription: Subscription;

	constructor(
		public app: AppService,
		public mobile: MobileService,
		private api: ApiService,
		private route: ActivatedRoute
	) { }

	ngOnInit() {
		this.routeSubscription = this.route.params.subscribe(params => {
			this.mobile.buildingId = params['buildingId'];
			this.reload();
		});
	}

	ngOnDestroy() {
		this.routeSubscription.unsubscribe();
	}

	reload() {
		this.moduleList = null;
		this.api.mobile.listModules(this.mobile.buildingId, response => {
			this.moduleList = response.data;
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

}
