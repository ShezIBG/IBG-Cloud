import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-billing-area-details',
	templateUrl: './billing-area-details.component.html'
})
export class BillingAreaDetailsComponent implements OnInit, OnDestroy {

	owner;
	area_id;
	data;
	destroyed;

	private sub: any;

	constructor(
		public app: AppService,
		private api: ApiService,
		private route: ActivatedRoute
	) { }

	ngOnInit() {
		this.destroyed = false;
		this.sub = this.route.params.subscribe(params => {
			this.area_id = params['area'] || '';
			this.owner = params['owner'] || '';
			this.refresh();
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
		this.destroyed = true;
	}

	refresh() {
		this.api.billing.getArea(this.owner, this.area_id, response => {
			if (this.destroyed) return;
			this.data = response.data;

			this.app.header.clearAll();
			this.app.header.addCrumbs(response.data.breadcrumbs);
		}, response => {
			if (this.destroyed) return;
			this.app.notifications.showDanger(response.message);
		});
	}

}
