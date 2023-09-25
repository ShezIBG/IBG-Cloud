import { AppService } from './../../app.service';
import { ApiService } from './../../api.service';
import { ActivatedRoute } from '@angular/router';
import { Component, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-billing-client-details',
	templateUrl: './billing-client-details.component.html'
})
export class BillingClientDetailsComponent implements OnInit, OnDestroy {

	id;
	owner;
	tab;
	details;
	account;
	disabled = false;
	destroyed = false;

	transaction = {};

	Math = Math;

	private sub: any;

	constructor(
		public app: AppService,
		private api: ApiService,
		private route: ActivatedRoute,
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			this.id = params['client'];
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
		this.api.billing.getClient(this.owner, this.id, response => {
			if (!this.destroyed) {
				this.details = response.data.details;
				this.account = response.data.account;

				this.transaction = {
					show: false,
					account_id: this.account ? this.account.id : 0,
					type: 'cash',
					amount: 0,
					description: '',
					transaction_ref: ''
				};

				this.disabled = false;

				const baseRoute = '/billing/' + this.owner + '/client/' + this.id;
				this.app.header.clearAll();
				this.app.header.addCrumbs(response.data.breadcrumbs);
				this.app.header.addTab({ id: 'overview', title: 'Overview', route: baseRoute });
				if (this.account) {
					this.app.header.addTab({ id: 'invoices', title: 'Invoices', route: baseRoute + '/invoices' });
					this.app.header.addTab({ id: 'transactions', title: 'Transactions', route: baseRoute + '/transactions' });
				}
				this.app.header.addTab({ id: 'sites', title: 'Sites', route: baseRoute + '/sites' });
				this.app.header.setTab(this.tab);
			}
		}, response => {
			if (!this.destroyed) this.app.notifications.showDanger(response.message);
		});
	}

	cancelTransaction(t) {
		const currentId = this.id;

		if (confirm('Are you sure you want to cancel this transaction?')) {
			this.api.billing.cancelTransaction(t.id, () => {
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
			this.api.billing.deleteCard(item.payment_gateway_id, item.customer_type, item.customer_id, () => {
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
			this.api.billing.cancelMandate(item.payment_gateway_id, item.customer_type, item.customer_id, () => {
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

	createPaymentAccount() {
		if (confirm('Are you sure you want to create a payment account for this client?')) {
			this.api.billing.createClientAccount(this.owner, this.id, () => {
				this.refresh();
			}, response => {
				this.app.notifications.showDanger(response.message);
			});
		}
	}

}
