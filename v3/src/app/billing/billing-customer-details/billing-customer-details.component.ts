import { BillingCustomerEmailModalComponent } from './../billing-customer-email-modal/billing-customer-email-modal.component';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy, NgModuleRef } from '@angular/core';

@Component({
	selector: 'app-billing-customer-details',
	templateUrl: './billing-customer-details.component.html'
})
export class BillingCustomerDetailsComponent implements OnInit, OnDestroy {

	id;
	owner;
	tab;
	info;
	details;
	account;
	archiveWarnings = [];
	disabled = false;
	destroyed = false;

	transaction = {};

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
			this.owner = params['owner'];
			this.tab = params['tab'];
			this.refresh();
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
		this.destroyed = true;
	}

	refresh() {
		this.api.billing.getCustomer(this.owner, this.id, response => {
			if (!this.destroyed) {
				this.info = response.data.info;
				this.details = response.data.details;
				this.account = response.data.account;
				this.archiveWarnings = response.data.archive_warnings;

				this.transaction = {
					show: false,
					account_id: this.account ? this.account.id : '',
					type: 'cash',
					amount: 0,
					description: '',
					transaction_ref: ''
				};

				this.disabled = false;

				const baseRoute = '/billing/' + this.owner + '/customer/' + this.id;
				this.app.header.clearAll();
				this.app.header.addCrumbs(response.data.breadcrumbs);
				this.app.header.addTab({ id: 'overview', title: 'Overview', route: baseRoute });
				if (this.account) {
					this.app.header.addTab({ id: 'invoices', title: 'Invoices', route: baseRoute + '/invoices' });
					this.app.header.addTab({ id: 'transactions', title: 'Transactions', route: baseRoute + '/transactions' });
				}
				this.app.header.setTab(this.tab);
			}
		}, response => {
			if (!this.destroyed) this.app.notifications.showDanger(response.message);
		});
	}

	cancelTransaction(t) {
		const currentId = this.id;

		if (confirm('Are you sure you want to cancel this transaction?')) {
			this.api.billing.cancelTransaction(t.id, response => {
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
			this.api.billing.deleteCard(item.payment_gateway_id, item.customer_type, item.customer_id, response => {
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
			this.api.billing.cancelMandate(item.payment_gateway_id, item.customer_type, item.customer_id, response => {
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
		this.api.billing.newTransaction(this.transaction, () => {
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

		this.app.modal.open(BillingCustomerEmailModalComponent, this.moduleRef, {
			owner: this.owner,
			customers: [this.id]
		});
	}

}
