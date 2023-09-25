import { BillingService } from './../billing.service';
import { Router, ActivationEnd } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { ModuleComponent } from './../../shared/module/module.component';
import { Component, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-billing',
	template: `<router-outlet></router-outlet>`
})
export class BillingComponent extends ModuleComponent implements OnDestroy {

	private subs: any[] = [];

	constructor(
		public app: AppService,
		private api: ApiService,
		private router: Router,
		private billing: BillingService
	) {
		super(app);

		this.subs.push(billing.onOwnerChanged.subscribe(id => {
			this.api.billing.getNavigation(id, response => {
				this.app.sidebar.setMenuData(response.data);
			});
		}));

		this.subs.push(this.router.events.subscribe(event => {
			if (event instanceof ActivationEnd) {
				if (event.snapshot.params.owner) {
					this.billing.id = event.snapshot.params.owner;
				}
			}
		}));
	}

	ngOnDestroy() {
		this.subs.forEach(sub => sub.unsubscribe());
	}

}
