import { Location } from '@angular/common';
import { Router, ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-billing-customer-edit',
	templateUrl: './billing-customer-edit.component.html'
})
export class BillingCustomerEditComponent implements OnInit, OnDestroy {

	owner;
	details;
	archiveWarnings = [];
	disabled = false;

	private sub: any;

	constructor(
		private app: AppService,
		private api: ApiService,
		private router: Router,
		private route: ActivatedRoute,
		private location: Location
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			const id = params['customer'] || 'new';
			this.owner = params['owner'] || '';
			this.details = null;

			const success = response => {
				this.app.header.clearAll();
				this.app.header.addCrumbs(response.data.breadcrumbs);
				this.details = response.data.details || {};
				this.details.archived = !!this.details.archived;
				this.archiveWarnings = response.data.archive_warnings || [];
			}

			const fail = response => {
				this.app.notifications.showDanger(response.message);
			};

			if (id === 'new') {
				this.api.billing.newCustomer(this.owner, success, fail);
			} else {
				this.api.billing.getCustomer(this.owner, id, success, fail);
			}
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

	goBack() {
		this.location.back();
	}

	save() {
		this.disabled = true;
		this.api.billing.saveCustomer(this.details, response => {
			this.disabled = false;
			if (this.details.id === 'new') {
				this.router.navigate(['/billing', this.owner, 'customer', response.data]);
				this.app.notifications.showSuccess('Customer created.');
			} else {
				this.goBack();
				this.app.notifications.showSuccess('Customer updated.');
			}
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

	copyAddress() {
		this.details.invoice_address_line_1 = this.details.address_line_1;
		this.details.invoice_address_line_2 = this.details.address_line_2;
		this.details.invoice_address_line_3 = this.details.address_line_3;
		this.details.invoice_posttown = this.details.posttown;
		this.details.invoice_postcode = this.details.postcode;
	}

}
