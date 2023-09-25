import { Pagination } from './../../shared/pagination';
import { IspCustomerEmailModalComponent } from './../isp-customer-email-modal/isp-customer-email-modal.component';
import { ActivatedRoute } from '@angular/router';
import { IspService } from './../isp.service';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy, NgModuleRef } from '@angular/core';
import { MySQLDateToISOPipe } from './../../shared/mysql-date-to-iso.pipe';

declare var Mangler: any;

@Component({
	selector: 'app-isp-customers',
	templateUrl: './isp-customers.component.html',
	styleUrls: ['./isp-customers.component.less']
})
export class IspCustomersComponent implements OnInit, OnDestroy {
	// filters;
	csv_url;
	destroyed = false;
	list: any = null;
	search = '';
	pagination = new Pagination();
	filtered = {
		list: []
	};
	params: any = {};
	filters;

	
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

	private sub: any;

	constructor(
		private app: AppService,
		private api: ApiService,
		public isp: IspService,
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
		this.destroyed = true;
	}

	refresh() {
		this.params.archived = this.isp.showArchivedCustomers;
		this.params.active_contracts = this.isp.withActiveContracts;

		const filters = Mangler.clone(this.filters) || {};
		if (filters.withActiveContracts) filters.withActiveContracts = (filters.withActiveContracts || '').split(' ')[0];
		if (filters.showArchivedCustomers) filters.showArchivedCustomers = (filters.showArchivedCustomers || '').split(' ')[0];

		this.api.isp.listCustomers(Mangler.merge({}, [this.params, filters]), response => {
			this.list = response.data.list;
			this.csv_url = response.data.csv_url;
			this.list.forEach(c => c.selected = false);
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

		this.app.modal.open(IspCustomerEmailModalComponent, this.moduleRef, {
			owner_type: 'SI',
			owner_id: this.params.isp,
			customers: customers
		});
	}

	exportCustomerCSV(){
		if (this.csv_url) {
			let filters = Mangler.clone(this.filters) || {};
			if (filters.withActiveContracts) filters.withActiveContracts = (filters.withActiveContracts || '').split(' ')[0];
			if (filters.showArchivedCustomers) filters.showArchivedCustomers = (filters.showArchivedCustomers || '').split(' ')[0];
			filters = Mangler.merge({}, [this.params, filters]);

			const query = {};
			Mangler.each(filters, (k, v) => {
				if (Mangler.isArray(v)) {
					query[k] = v.join(',');
				} else if (v) {
					query[k] = v;
				}
			});

			window.open(this.csv_url + '?' + this.api.objectToQueryString(query), '_blank');

		}
	}


}
