import { ActivatedRoute } from '@angular/router';
import { BillingService } from './../billing.service';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy, Input } from '@angular/core';
import { Pagination } from 'app/shared/pagination';

@Component({
	selector: 'app-billing-contracts',
	templateUrl: './billing-contracts.component.html'
})
export class BillingContractsComponent implements OnInit, OnDestroy {

	@Input() hideNew = false;

	owner;
	filtered;
	list: any = null;
	templates: any = null;

	count = {
		templates: 0,
	};
	search = '';
	pagination = new Pagination();

	private sub: any;

	constructor(
		private app: AppService,
		private api: ApiService,
		public billing: BillingService,
		private route: ActivatedRoute
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			this.filtered = params['customer'] || params['si'] || params['client'];
			this.owner = params['owner'];
			this.api.billing.listContracts(params, response => {
				this.list = response.data;
			}, response => {
				this.app.notifications.showDanger(response.message);
			});

			this.api.billing.listContractTemplates(params, response => {
				this.templates = response.data;
			});
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

}
