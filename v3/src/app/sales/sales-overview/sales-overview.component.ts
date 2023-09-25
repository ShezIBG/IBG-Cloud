import { AppService } from './../../app.service';
import { ApiService } from './../../api.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-sales-overview',
	templateUrl: './sales-overview.component.html',
	styleUrls: ['./sales-overview.component.less']
})
export class SalesOverviewComponent implements OnInit, OnDestroy {

	data;

	private sub: any;

	constructor(
		public app: AppService,
		private api: ApiService
	) { }

	ngOnInit() {
		this.sub = this.app.productOwnerChanged.subscribe(() => this.refresh());
		this.refresh();
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

	refresh() {
		this.api.sales.getOverview(this.app.selectedProductOwner, response => {
			this.data = response.data;
			this.app.resolveProductOwners(response);
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

}
