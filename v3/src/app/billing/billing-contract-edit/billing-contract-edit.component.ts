import { MySQLDateToISOPipe } from './../../shared/mysql-date-to-iso.pipe';
import { Location } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-billing-contract-edit',
	templateUrl: './billing-contract-edit.component.html',
	styleUrls: ['./billing-contract-edit.component.less']
})
export class BillingContractEditComponent implements OnInit, OnDestroy {

	owner;
	details;
	list: any = {};
	disabled = false;

	start_date = null;
	end_date = null;

	selectedBuilding = null;

	get selectedArea() {
		if (!this.selectedBuilding) return null;

		let found = null;
		this.selectedBuilding.areas.forEach(area => {
			if (area.id === this.details.area_id) found = area;
		});
		return found;
	}

	private sub: any;

	constructor(
		private app: AppService,
		private api: ApiService,
		private route: ActivatedRoute,
		private location: Location
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			const id = params['contract'] || 'new';
			this.owner = params['owner'] || '';
			this.details = null;
			this.list = {};
			this.selectedBuilding = null;

			const success = response => {
				this.app.header.clearAll();
				this.app.header.addCrumbs(response.data.breadcrumbs);
				this.details = response.data.details || {};
				this.list = response.data.list || {};

				// Process data
				this.start_date = MySQLDateToISOPipe.stringToDate(this.details.start_date);
				this.end_date = MySQLDateToISOPipe.stringToDate(this.details.end_date);
				this.details.skip_past_invoices = !!this.details.skip_past_invoices;
				this.details.provides_access = !!this.details.provides_access;
				this.details.instant_activation_email = !!this.details.instant_activation_email;

				this.details.invoices.forEach(invoice => {
					invoice.initial_card_payment = !!invoice.initial_card_payment;
					invoice.charge_card_if_dd_fails = !!invoice.charge_card_if_dd_fails;
					invoice.auto_charge_saved_card = !!invoice.auto_charge_saved_card;
					invoice.manual_authorisation = !!invoice.manual_authorisation;
					invoice.mandatory_dd = !!invoice.mandatory_dd;
					invoice.lines.forEach(line => {
						line.pro_rata = !!line.pro_rata;
					});
				});

				// Find and select the building
				this.selectedBuilding = null;
				this.list.buildings.forEach(building => {
					building.areas.forEach(area => {
						if (area.id === this.details.area_id) {
							this.selectedBuilding = building;
						}
					});
				});
			}

			const fail = response => {
				this.app.notifications.showDanger(response.message);
			};

			if (id === 'new') {
				let customerType = '';
				let customerId = '';

				if (params['customer']) { customerType = 'CU'; customerId = params['customer']; }
				if (params['si']) { customerType = 'SI'; customerId = params['si']; }
				if (params['client']) { customerType = 'C'; customerId = params['client']; }

				const template = params['template'] || '';

				this.api.billing.newContract(this.owner, customerType, customerId, template, success, fail);
			} else {
				this.api.billing.getContract(id, success, fail);
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
		this.details.start_date = MySQLDateToISOPipe.dateToString(this.start_date);
		this.details.end_date = MySQLDateToISOPipe.dateToString(this.end_date);

		this.disabled = true;
		this.api.billing.saveContract(this.details, response => {
			this.disabled = false;
			this.goBack();
			this.app.notifications.showSuccess(this.details.id === 'new' ? 'Contract created.' : 'Contract updated.');
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

	addInvoice() {
		this.details.invoices.push({
			id: 'new',
			invoice_entity_id: null,
			description: '',
			frequency: 'monthly+',
			card_payment_gateway: null,
			dd_payment_gateway: null,
			cutoff_day: 15,
			issue_day: 21,
			payment_day: 28,
			initial_card_payment: true,
			charge_card_if_dd_fails: true,
			charge_card_after_days: 2,
			auto_charge_saved_card: true,
			retry_dd_times: 1,
			vat_rate: 20,

			lines: []
		});

		this.details.invoices = this.details.invoices.slice();
	}

	deleteInvoice(invoice) {
		const i = this.details.invoices.indexOf(invoice);
		if (i !== -1) {
			if (invoice.id !== 'new') {
				if (!this.details.invoices_deleted) this.details.invoices_deleted = [];
				this.details.invoices_deleted.push(invoice.id);
			}
			this.details.invoices.splice(i, 1);
			this.details.invoices = this.details.invoices.slice();
		}
	}

	addInvoiceLine(invoice) {
		invoice.lines.push({
			id: 'new',
			type: 'custom',
			isp_package_id: null,
			icon: '',
			description: '',
			unit_price: 0,
			quantity: 1,
			pro_rata: true,
			charge_type: 'always'
		});

		invoice.lines = invoice.lines.slice();
	}

	deleteInvoiceLine(invoice, line) {
		const i = invoice.lines.indexOf(line);
		if (i !== -1) {
			if (line.id !== 'new') {
				if (!invoice.lines_deleted) invoice.lines_deleted = [];
				invoice.lines_deleted.push(line.id);
			}
			invoice.lines.splice(i, 1);
			invoice.lines = invoice.lines.slice();
		}
	}

	findPackage(id) {
		if (!id || !this.selectedBuilding) return null;
		let found = null;
		this.selectedBuilding.packages.forEach(p => {
			if (p.id === id) found = p;
		});
		return found;
	}

	buildingChanged() {
		this.details.area_id = null;
		this.details.invoices.forEach(invoice => {
			invoice.lines.forEach(line => {
				if (line.type === 'isp_package' || line.type === 'isp_package_custom') line.isp_package_id = null;
			});
		});
	}

	invoiceLineTypeChanged(line) {
		switch (line.type) {
			case 'custom':
				line.isp_package_id = null;
				break;

			case 'isp_package':
				line.description = '';
				line.unit_price = 0;
				break;

			case 'isp_package_custom':
				line.description = '';
				this.packageChanged(line);
				break;

			case 'isp_routers':
			case 'utility_e':
			case 'utility_g':
			case 'utility_w':
			case 'utility_h':
			case 'utility_s':
				line.isp_package_id = null;
				line.pro_rata = false;
				line.quantity = 0;
				line.charge_type = 'always';
				break;
		}
	}

	packageChanged(line) {
		const p = this.findPackage(line.isp_package_id);
		line.unit_price = p ? p.monthly_price : 0;
	}

}
