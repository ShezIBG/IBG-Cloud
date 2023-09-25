import { Router, ActivatedRoute } from '@angular/router';
import { AppService } from './../../app.service';
import { ApiService } from './../../api.service';
import { Component, OnInit } from '@angular/core';

@Component({
	selector: 'app-billing-auto-select',
	template: ''
})
export class BillingAutoSelectComponent implements OnInit {

	constructor(
		private app: AppService,
		private api: ApiService,
		private route: ActivatedRoute,
		private router: Router
	) { }

	ngOnInit() {
		this.api.billing.getFirstOwner(response => {
			this.router.navigate([response.data], { relativeTo: this.route, replaceUrl: true });
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

}
