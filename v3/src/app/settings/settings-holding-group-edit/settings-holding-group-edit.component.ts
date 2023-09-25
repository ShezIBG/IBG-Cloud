import { SortcodePipe } from './../../shared/sortcode.pipe';
import { AppService } from './../../app.service';
import { Location } from '@angular/common';
import { Router, ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-settings-holding-group-edit',
	templateUrl: './settings-holding-group-edit.component.html'
})
export class SettingsHoldingGroupEditComponent implements OnInit, OnDestroy {

	private sub: any;

	id;
	details;
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
			const level = params['level'];
			const levelId = params['levelId'];

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
				this.api.settings.newHoldingGroup(level, levelId, success, fail);
			} else {
				this.api.settings.getHoldingGroup(this.id, success, fail);
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
		this.api.settings.saveHoldingGroup(this.details, response => {
			this.disabled = false;
			if (this.details.id === 'new') {
				this.router.navigate(['/settings/holding-group', response.data]);
				this.app.notifications.showSuccess('Holding group created.');
			} else {
				this.goBack();
				this.app.notifications.showSuccess('Holding group updated.');
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
