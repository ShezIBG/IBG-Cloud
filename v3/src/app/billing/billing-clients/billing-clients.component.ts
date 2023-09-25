import { BillingService } from './../billing.service';
import { AppService } from './../../app.service';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-billing-clients',
	templateUrl: './billing-clients.component.html'
})
export class BillingClientsComponent implements OnInit, OnDestroy {

	list: any = null;
	count = { clients: 0 };
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
			this.api.billing.listClients(params, response => {
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
