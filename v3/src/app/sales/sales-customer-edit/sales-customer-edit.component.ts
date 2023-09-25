import { Location } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

declare var Mangler: any;

@Component({
	selector: 'app-sales-customer-edit',
	templateUrl: './sales-customer-edit.component.html'
})
export class SalesCustomerEditComponent implements OnInit, OnDestroy {

	id;
	details;
	list;
	disabled = false;

	private sub: any;

	constructor(
		private app: AppService,
		private api: ApiService,
		private route: ActivatedRoute,
		private location: Location
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			const owner = params['owner'];

			this.id = params['customerId'] || 'new';
			this.details = null;

			const success = response => {
				this.app.header.clearAll();
				this.app.header.addCrumbs(response.data.breadcrumbs);
				this.details = response.data.details || {};
				this.list = response.data.list || {};
			}

			const fail = response => {
				this.app.notifications.showDanger(response.message);
			};

			if (this.id === 'new') {
				this.api.sales.newCustomer(owner, success, fail);
			} else {
				this.api.sales.getCustomer(this.id, success, fail);
			}
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

	goBack() {
		this.location.back();
	}

	siChanged() {
		if (this.id !== 'new') return;

		this.api.sales.newCustomerLists(this.details.system_integrator_id, response => {
			this.list.users = response.data.list.users || [];

			if (this.details.user_id !== null) {
				if (Mangler.find(this.list.users, { id: this.details.user_id }).length === 0) {
					this.details.user_id = null;
				}
			}
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	save() {
		this.disabled = true;
		this.api.sales.saveCustomer(this.details, () => {
			this.disabled = false;
			this.goBack();
			if (this.details.id === 'new') {
				this.app.notifications.showSuccess('Customer created.');
			} else {
				this.app.notifications.showSuccess('Customer updated.');
			}
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

}
