import { ActivatedRoute } from '@angular/router';
import { BillingService } from './../billing.service';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

declare var Mangler: any;

@Component({
	selector: 'app-billing-invoice-entities',
	templateUrl: './billing-invoice-entities.component.html'
})
export class BillingInvoiceEntitiesComponent implements OnInit, OnDestroy {

	list: any = null;
	count = { list: 0 };
	search = '';
	params: any = {};

	private sub: any;

	constructor(
		private app: AppService,
		private api: ApiService,
		public billing: BillingService,
		private route: ActivatedRoute
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			this.params = Mangler.clone(params);
			this.refresh();
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

	refresh() {
		this.params.archived = this.billing.showArchivedInvoiceEntities;
		this.params.active_contracts = this.billing.invoiceEntitiesWithActiveContracts;

		this.api.billing.listInvoiceEntities(this.params, response => {
			this.list = response.data;
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

}
