import { BillingCustomerEmailModalComponent } from './../billing-customer-email-modal/billing-customer-email-modal.component';
import { ActivatedRoute } from '@angular/router';
import { BillingService } from './../billing.service';
import { Pagination } from 'app/shared/pagination';
import { Component, OnInit, OnDestroy, NgModuleRef } from '@angular/core';
import { AppService } from 'app/app.service';
import { ApiService } from 'app/api.service';
import { UIElementModalComponent } from 'app/shared/ui-element-modal/ui-element-modal.component';

declare var Mangler: any;

@Component({
	selector: 'app-billing-customers-in-arrears',
	templateUrl: './billing-customers-in-arrears.component.html',
	styleUrls: ['./billing-customers-in-arrears.component.less']
})
export class BillingCustomersInArrearsComponent implements OnInit, OnDestroy {

	get selectedCount() {
		let count = 0;
		if (this.list) this.list.forEach(c => count += c.selected ? 1 : 0);
		return count;
	}

	get selectAll() {
		if (this.filtered.list) {
			let count = 0;
			this.filtered.list.forEach(c => count += c.selected ? 1 : 0);
			return count === this.filtered.list.length;
		}
		return false;
	}
	set selectAll(value) {
		if (this.filtered.list) this.filtered.list.forEach(item => {
			item.selected = value
		});
	}

	list: any = null;
	search = '';
	pagination = new Pagination();
	filtered = {
		list: []
	};
	params: any = {};
	columns = [];
	csvUrl = '';

	private sub: any;

	constructor(
		private app: AppService,
		private api: ApiService,
		public billing: BillingService,
		private route: ActivatedRoute,
		private moduleRef: NgModuleRef<any>
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			this.params = Mangler.clone(params);
			this.refresh();
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

	refresh() {
		this.params.active_contracts = this.billing.withActiveContracts;

		this.api.billing.listCustomersInArrears(this.params, response => {
			this.columns = response.data.columns;
			this.csvUrl = response.data.csv_url;

			this.list = response.data.list;
			this.list.forEach(c => c.selected = false);

			console.log(this.list);

			const baseRoute = '/billing/' + this.params.owner + '/customer';

			this.app.header.clearAll();
			this.app.header.addCrumb({ description: 'Customers' });
			this.app.header.addTab({ id: 'customers', title: 'All Customers', route: baseRoute });
			this.app.header.addTab({ id: 'in-arrears', title: 'In Arrears', route: baseRoute + '/in-arrears' });
			this.app.header.setTab('in-arrears');
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	sendEmail() {
		const customers = [];
		this.list.forEach(c => {
			if (c.selected) customers.push(c.id);
		});
		if (customers.length === 0) return;

		const modalSub = this.app.modal.modalService.modalClosed.subscribe(() => {
			modalSub.unsubscribe();
		});

		this.app.modal.open(BillingCustomerEmailModalComponent, this.moduleRef, {
			owner: this.params.owner,
			customers: customers
		});
	}

	editUI() {
		const modalSub = this.app.modal.modalService.modalClosed.subscribe(() => {
			this.refresh();
			modalSub.unsubscribe();
		});

		this.app.modal.open(UIElementModalComponent, null, 'billing_customers_in_arrears');
	}

	exportCSV() {
		window.open(this.csvUrl, '_blank');
	}

}
