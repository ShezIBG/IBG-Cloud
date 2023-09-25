import { SortcodePipe } from './../../shared/sortcode.pipe';
import { AppService } from './../../app.service';
import { Router, ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { Component, OnInit, OnDestroy } from '@angular/core';
import { Location } from '@angular/common';

@Component({
	selector: 'app-settings-service-provider-edit',
	templateUrl: './settings-service-provider-edit.component.html'
})
export class SettingsServiceProviderEditComponent implements OnInit, OnDestroy {

	private sub: any;

	id;
	details;
	permissions;
	disabled = false;

	constructor(
		private app: AppService,
		private api: ApiService,
		private router: Router,
		private route: ActivatedRoute,
		private location: Location
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			this.id = params['id'] || 'new';
			this.details = null;

			const success = response => {
				this.app.header.clearAll();
				this.app.header.addCrumbs(response.data.breadcrumbs);
				this.details = response.data.details || {};
				this.details.permissions = response.data.permissions;

				this.formatSortCode();
			}

			const fail = response => {
				this.app.notifications.showDanger(response.message);
			};

			if (this.id === 'new') {
				this.api.settings.newServiceProvider(success, fail);
			} else {
				this.api.settings.getServiceProvider(this.id, success, fail);
			}
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

	formatSortCode() {
		this.details.bank_sort_code = SortcodePipe.transform(this.details.bank_sort_code);
	}

	goBack() {
		this.location.back();
	}

	save() {
		this.disabled = true;
		this.api.settings.saveServiceProvider(this.details, response => {
			this.disabled = false;
			if (this.details.id === 'new') {
				this.router.navigate(['/settings/service-provider', response.data]);
				this.app.notifications.showSuccess('Service provider created.');
			} else {
				this.goBack();
				this.app.notifications.showSuccess('Service provider updated.');
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
