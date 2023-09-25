import { DecimalPipe } from './../../shared/decimal.pipe';
import { Location } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router';
import { SalesService } from './../sales.service';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';
import { MySQLDateToISOPipe } from 'app/shared/mysql-date-to-iso.pipe';

declare var Mangler: any;

@Component({
	selector: 'app-sales-project-edit',
	templateUrl: './sales-project-edit.component.html'
})
export class SalesProjectEditComponent implements OnInit, OnDestroy {

	id;
	details;
	has_labour;
	has_subscriptions;
	list;
	disabled = false;

	oldStage = null;
	showStageNotes = false;
	customer = null;
	newOwner;

	get canChangeExclusions() {
		return this.oldStage === 'lead' || this.oldStage === 'survey' || this.details.stage === 'lead' || this.details.stage === 'survey';
	}

	get exclude_labour() { return !!this.details.exclude_labour; }
	set exclude_labour(value) { this.details.exclude_labour = value ? 1 : 0; }

	get exclude_subscriptions() { return !!this.details.exclude_subscriptions; }
	set exclude_subscriptions(value) { this.details.exclude_subscriptions = value ? 1 : 0; }

	get is_public() { return !!this.details.is_public; }
	set is_public(value) { this.details.is_public = value ? 1 : 0; }

	get assigned_to_name() {
		if (this.details.user_id) {
			const user = Mangler.findOne(this.list.users, { id: this.details.user_id });
			if (user) return user.name;
		}
		return '';
	}

	private sub: any;

	constructor(
		private app: AppService,
		private api: ApiService,
		private sales: SalesService,
		private route: ActivatedRoute,
		private router: Router,
		private location: Location
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			this.newOwner = params['owner'];
			this.id = params['projectId'] || 'new';
			this.reloadProject();
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

	reloadProject() {
		this.customer = null;

		const success = response => {
			if (this.id === 'new') {
				this.app.header.clearAll();
				this.app.header.addCrumb({ description: 'New Project' });
			} else {
				this.app.header.setTab('details');
			}

			this.details = response.data.details || {};
			this.details.quote_date = MySQLDateToISOPipe.stringToDate(this.details.quote_date);
			this.details.expiry_date = MySQLDateToISOPipe.stringToDate(this.details.expiry_date);

			this.has_labour = response.data.has_labour || false;
			this.has_subscriptions = response.data.has_subscriptions || false;
			this.list = response.data.list || {};

			this.oldStage = this.details.stage;
			this.showStageNotes = false;

			this.formatNumbers();
			this.customerChanged();
		}

		const fail = response => {
			this.app.notifications.showDanger(response.message);
		};

		if (this.id === 'new') {
			this.api.sales.newProject(this.newOwner, success, fail);
		} else {
			this.api.sales.getProject(this.id, success, fail);
		}
	}

	formatNumbers(addThousandSeparators: boolean = true) {
		this.details.vat_rate = DecimalPipe.transform(this.details.vat_rate, 2, 2, addThousandSeparators);
	}

	goBack() {
		this.location.back();
	}

	siChanged() {
		if (this.id !== 'new') return;

		this.details.customer_id = 'new';
		this.customerChanged();

		this.api.sales.newProjectLists(this.details.system_integrator_id, response => {
			this.list.users = response.data.list.users || [];
			this.list.customers = response.data.list.customers || [];

			if (this.details.user_id !== null) {
				if (Mangler.find(this.list.users, { id: this.details.user_id }).length === 0) {
					this.details.user_id = null;
				}
			}
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	customerChanged() {
		this.customer = null;
		if (!this.details || this.id !== 'new') return;

		const customerId = this.details.customer_id;
		if (!customerId || customerId === 'new') return;

		this.api.sales.getCustomer(customerId, response => {
			this.customer = response.data.details;
		});
	}

	save() {
		this.disabled = true;

		this.formatNumbers(false);
		const data = Mangler.clone(this.details);
		this.formatNumbers();

		data.quote_date = MySQLDateToISOPipe.dateToString(data.quote_date);
		data.expiry_date = MySQLDateToISOPipe.dateToString(data.expiry_date);

		this.api.sales.saveProject(data, response => {
			this.disabled = false;
			if (this.details.id === 'new') {
				this.app.notifications.showSuccess('Project created.');
				this.router.navigate(['../..', response.data], { relativeTo: this.route });
			} else {
				this.app.notifications.showSuccess('Project updated.');
				this.sales.setProjectHeader(this.details.id, this.details.description, this.details.project_no, this.sales.projectPricing);
				this.reloadProject();
			}
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

	stageUpdated(newStage) {
		this.showStageNotes = this.id === 'new' || newStage !== this.oldStage;
	}

	copyCustomerAddress() {
		if (this.customer) {
			this.details.address_line_1 = this.customer.address_line_1;
			this.details.address_line_2 = this.customer.address_line_2;
			this.details.address_line_3 = this.customer.address_line_3;
			this.details.posttown = this.customer.posttown;
			this.details.postcode = this.customer.postcode;
			this.details.phone_number = this.customer.phone_number;
		}
	}

	copyCustomerContact() {
		if (this.customer) {
			this.details.contact_name = this.customer.contact_name;
			this.details.contact_position = this.customer.contact_position;
			this.details.contact_email = this.customer.contact_email;
			this.details.contact_mobile = this.customer.contact_mobile;
		}
	}

}
