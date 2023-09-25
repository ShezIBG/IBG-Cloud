import { IspCustomerEmailModalComponent } from './../isp-customer-email-modal/isp-customer-email-modal.component';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy, NgModuleRef } from '@angular/core';

@Component({
	selector: 'app-isp-customer-details',
	templateUrl: './isp-customer-details.component.html'
})
export class IspCustomerDetailsComponent implements OnInit, OnDestroy {

	id;
	isp_id;
	info;
	details;
	account;
	archiveWarnings = [];
	disabled = false;
	destroyed = false;

	transaction = {
		show: false,
		account_id: 0,
		type: 'cash',
		amount: 0,
		description: '',
		transaction_ref: ''
	};

	Math = Math;

	private sub: any;

	constructor(
		public app: AppService,
		private api: ApiService,
		private route: ActivatedRoute,
		private moduleRef: NgModuleRef<any>
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			this.id = params['customer'];
			this.isp_id = params['isp'];
			const baseRoute = '/isp/' + this.isp_id + '/customer/' + this.id;
			const tab = params['tab'];

			this.refresh(response => {
				this.app.header.clearAll();
				this.app.header.addCrumbs(response.data.breadcrumbs);
				this.app.header.addTab({ id: 'overview', title: 'Overview', route: baseRoute });
				this.app.header.addTab({ id: 'invoices', title: 'Invoices', route: baseRoute + '/invoices' });
				this.app.header.addTab({ id: 'transactions', title: 'Transactions', route: baseRoute + '/transactions' });
				this.app.header.setTab(tab);
			});
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
		this.destroyed = true;
	}

	refresh(callback = null) {
		this.api.isp.getCustomer(this.id, response => {
			if (!this.destroyed) {
				this.info = response.data.info;
				this.details = response.data.details;
				this.account = response.data.account;
				this.archiveWarnings = response.data.archive_warnings;

				this.transaction = {
					show: false,
					account_id: this.account.id,
					type: 'cash',
					amount: 0,
					description: '',
					transaction_ref: ''
				};

				this.disabled = false;
				if (callback) callback(response);
			}
		}, response => {
			if (!this.destroyed) this.app.notifications.showDanger(response.message);
		});
	}

	cancelTransaction(t) {
		const currentId = this.id;

		if (confirm('Are you sure you want to cancel this transaction?')) {
			this.api.isp.cancelTransaction(t.id, () => {
				this.app.notifications.showSuccess('Success', 'Cancellation request is being processed.');

				setTimeout(() => {
					if (!this.destroyed && this.id === currentId) {
						this.refresh();
					}
				}, 5000);
			}, response => {
				this.app.notifications.showDanger(response.message);
			});
		}
	}

	deleteCard(item) {
		if (confirm('Are you sure you want to delete this payment card?')) {
			this.api.isp.deleteCard(item.payment_gateway_id, item.customer_type, item.customer_id, () => {
				this.app.notifications.showSuccess('Success', 'Payment card removed.');
				this.refresh();
			}, response => {
				this.app.notifications.showDanger(response.message);
			});
		}
	}

	cancelMandate(item) {
		const currentId = this.id;

		if (confirm('Are you sure you want to cancel the Direct Debit mandate?')) {
			this.api.isp.cancelMandate(item.payment_gateway_id, item.customer_type, item.customer_id, () => {
				this.app.notifications.showSuccess('Success', 'Cancellation request is being processed.');

				setTimeout(() => {
					if (!this.destroyed && this.id === currentId) {
						this.refresh();
					}
				}, 5000);
			}, response => {
				this.app.notifications.showDanger(response.message);
			});
		}
	}

	addTransaction() {
		this.disabled = true;
		this.api.isp.newTransaction(this.transaction, () => {
			this.app.notifications.showSuccess('Transaction created.');
			this.refresh();
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

	sendEmail() {
		const modalSub = this.app.modal.modalService.modalClosed.subscribe(() => {
			modalSub.unsubscribe();
		});

		this.app.modal.open(IspCustomerEmailModalComponent, this.moduleRef, {
			owner_type: 'SI',
			owner_id: this.isp_id,
			customers: [this.id]
		});
	}

}
