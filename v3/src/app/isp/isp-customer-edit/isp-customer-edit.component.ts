import { Location } from '@angular/common';
import { Router, ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-isp-customer-edit',
	templateUrl: './isp-customer-edit.component.html'
})
export class IspCustomerEditComponent implements OnInit, OnDestroy {

	isp;
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
			this.isp = params['isp'] || '';
			this.details = null;

			const success = response => {
				this.app.header.clearAll();
				this.app.header.addCrumbs(response.data.breadcrumbs);
				this.details = response.data.details || {};
				this.details.archived = !!this.details.archived;
				this.details.allow_login = !!this.details.allow_login;
				this.archiveWarnings = response.data.archive_warnings || [];
			}

			const fail = response => {
				this.app.notifications.showDanger(response.message);
			};

			if (id === 'new') {
				this.api.isp.newCustomer(this.isp, success, fail);
			} else {
				this.api.isp.getCustomer(id, success, fail);
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
		this.api.isp.saveCustomer(this.details, response => {
			this.disabled = false;
			if (this.details.id === 'new') {
				this.router.navigate(['/isp', this.isp, 'customer', response.data]);
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

	generatePassword() {
		const length = 8;
		const validCharacters = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Removed ambiguous characters: O, 0, I, l, 1
		let password = '';
		for (var i = 0; i < length; i++) {
			password += validCharacters.charAt(Math.floor(Math.random() * validCharacters.length));
		}

		this.details.password = password;
	}

}
