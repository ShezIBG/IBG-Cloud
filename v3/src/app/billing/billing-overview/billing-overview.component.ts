import { ActivatedRoute } from '@angular/router';
import { BillingService } from './../billing.service';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-billing-overview',
	templateUrl: './billing-overview.component.html'
})
export class BillingOverviewComponent implements OnInit, OnDestroy {

	owner;
	overview;
	timer;
	destroyed;

	private sub: any;

	constructor(
		private app: AppService,
		private api: ApiService,
		public billing: BillingService,
		private route: ActivatedRoute
	) { }

	ngOnInit() {
		this.destroyed = false;
		this.sub = this.route.params.subscribe(params => {
			this.owner = params['owner'];
			this.refresh();
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
		this.destroyed = true;
		clearTimeout(this.timer);
	}

	refresh() {
		clearTimeout(this.timer);

		this.api.billing.getOverview(this.owner, response => {
			if (this.destroyed) return;
			this.overview = response.data;
			this.timer = setTimeout(() => this.refresh(), 30000);
		}, response => {
			if (this.destroyed) return;
			this.app.notifications.showDanger(response.message);
			this.timer = setTimeout(() => this.refresh(), 30000);
		});
	}

}
