import { AppService } from './../../app.service';
import { ApiService } from './../../api.service';
import { Component, OnInit } from '@angular/core';

@Component({
	selector: 'app-payment-overdue',
	templateUrl: './payment-overdue.component.html',
	styleUrls: ['../auth.module.less']
})
export class PaymentOverdueComponent implements OnInit {

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

	goToAccount() {
		this.app.redirect(this.info.account_url);
	}

}
