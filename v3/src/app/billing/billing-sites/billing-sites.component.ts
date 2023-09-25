import { ActivatedRoute } from '@angular/router';
import { BillingService } from './../billing.service';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-billing-sites',
	templateUrl: './billing-sites.component.html'
})
export class BillingSitesComponent implements OnInit, OnDestroy {

	list: any = null;
	count = { sites: 0 };
	search = '';

	private sub: any;

	constructor(
		private app: AppService,
		private api: ApiService,
		public billing: BillingService,
		private route: ActivatedRoute
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			this.api.billing.listBuildings(params, response => {
				this.list = response.data;
			}, response => {
				this.app.notifications.showDanger(response.message);
			});
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

}
