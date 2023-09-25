import { MySQLDateToISOPipe } from './../../shared/mysql-date-to-iso.pipe';
import { IspInvoiceCounterEditModalComponent } from './../isp-invoice-counter-edit-modal/isp-invoice-counter-edit-modal.component';
import { ActivatedRoute } from '@angular/router';
import { IspService } from './../isp.service';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy, NgModuleRef } from '@angular/core';
import { Pagination } from 'app/shared/pagination';

declare var Mangler: any;

@Component({
	selector: 'app-isp-invoices',
	templateUrl: './isp-invoices.component.html'
})
export class IspInvoicesComponent implements OnInit, OnDestroy {

	isp_id;
	params;
	list: any = null;
	csv_url;
	counter: any = null;
	pagination = new Pagination();
	search = '';
	destroyed = false;
	timer;
	filters;

	get status_not_approved() { return this.getStatusFilter('not_approved'); }
	set status_not_approved(value) { this.setStatusFilter('not_approved', value); }

	get status_outstanding() { return this.getStatusFilter('outstanding'); }
	set status_outstanding(value) { this.setStatusFilter('outstanding', value); }

	get status_paid() { return this.getStatusFilter('paid'); }
	set status_paid(value) { this.setStatusFilter('paid', value); }

	get status_cancelled() { return this.getStatusFilter('cancelled'); }
	set status_cancelled(value) { this.setStatusFilter('cancelled', value); }

	private sub: any;

	constructor(
		private app: AppService,
		private api: ApiService,
		public isp: IspService,
		private route: ActivatedRoute,
		private moduleRef: NgModuleRef<any>
	) { }

	ngOnInit() {
		this.destroyed = false;
		this.sub = this.route.params.subscribe(params => {
			this.params = params;

			if (!params['customer']) {
				// Set up additional filters
				if (!this.isp.invoiceFilters) {
					const date_from = new Date();
					const date_to = new Date();
					date_from.setMonth(date_from.getMonth() - 1);

					this.isp.invoiceFilters = {
						date_from,
						date_to,
						status: ['not_approved', 'outstanding', 'paid']
					};
				}

				this.filters = this.isp.invoiceFilters;
			}

			this.refresh();
		});
	}

	ngOnDestroy() {
		clearTimeout(this.timer);
		this.destroyed = true;
		this.sub.unsubscribe();
	}

	refresh() {
		clearTimeout(this.timer);
		this.isp_id = this.params['isp'];

		const filters = Mangler.clone(this.filters) || {};
		if (filters.date_from) filters.date_from = (MySQLDateToISOPipe.dateToString(filters.date_from) || '').split(' ')[0];
		if (filters.date_to) filters.date_to = (MySQLDateToISOPipe.dateToString(filters.date_to) || '').split(' ')[0];

		this.api.isp.listInvoices(Mangler.merge({}, [this.params, filters]), response => {
			if (this.destroyed) return;
			this.list = response.data.list;
			this.counter = response.data.counter;
			this.csv_url = response.data.csv_url;
		}, response => {
			if (this.destroyed) return;
			this.app.notifications.showDanger(response.message);
		});
	}

	timedRefresh() {
		clearTimeout(this.timer);
		this.timer = setTimeout(() => this.refresh(), 500);
	}

	editCounter() {
		const modalSub = this.app.modal.modalService.modalClosed.subscribe(() => {
			modalSub.unsubscribe();
			this.refresh();
		});

		this.app.modal.open(IspInvoiceCounterEditModalComponent, this.moduleRef, {
			owner_type: 'SI',
			owner_id: this.isp_id
		});
	}

	getStatusFilter(status) {
		if (this.filters) return this.filters.status.indexOf(status) !== -1;
		return false;
	}

	setStatusFilter(status, state) {
		if (this.filters) {
			const i = this.filters.status.indexOf(status);
			if (state) {
				if (i === -1) this.filters.status.push(status);
			} else {
				if (i !== -1) {
					this.filters.status.splice(i, 1);
					this.filters.status = this.filters.status.slice();
				}
			}
		}
	}

	exportSageCSV() {
		if (this.csv_url) {
			let filters = Mangler.clone(this.filters) || {};
			if (filters.date_from) filters.date_from = (MySQLDateToISOPipe.dateToString(filters.date_from) || '').split(' ')[0];
			if (filters.date_to) filters.date_to = (MySQLDateToISOPipe.dateToString(filters.date_to) || '').split(' ')[0];
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
