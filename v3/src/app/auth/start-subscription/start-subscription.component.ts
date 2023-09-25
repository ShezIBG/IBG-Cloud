import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit } from '@angular/core';

@Component({
	selector: 'app-start-subscription',
	templateUrl: './start-subscription.component.html',
	styleUrls: ['../auth.module.less']
})
export class StartSubscriptionComponent implements OnInit {

	info;

	constructor(
		public app: AppService,
		private api: ApiService
	) { }

	ngOnInit() {
		this.api.auth.getBillingAccount(response => {
			this.info = response.data;
		}, response => {
			this.app.notifications.showDanger(response.message);
			this.api.logout();
		});
	}

	enterBankDetails() {
		this.api.auth.getCustomerMandateUrl(response => {
			this.app.redirect(response.data);
		}, response => {
			this.app.notifications.showDanger(response.message);
			this.api.logout();
		});
	}

}
