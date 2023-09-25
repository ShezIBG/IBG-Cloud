import { DecimalPipe } from './../../shared/decimal.pipe';
import { Router, ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';
import { MySQLDateToISOPipe } from 'app/shared/mysql-date-to-iso.pipe';
import { Pagination } from 'app/shared/pagination';

declare var Stripe: any;

@Component({
	selector: 'app-account-details',
	templateUrl: './account-details.component.html',
	styleUrls: ['./account-details.component.less']
})
export class AccountDetailsComponent implements OnInit, OnDestroy {

	id;
	token;
	details;
	sub;

	disabled = false;
	stripe = null;

	signatureRequired = false;
	paymentMethodRequired = false;
	signedBy = '';

	Math = Math;

	keepAlive;

	mode = 'account';

	cancelContract;
	cancelDate = null;
	cancelResult;

	upgradeContract;
	upgradeInfo;
	upgradePackage;

	supportContract;
	supportInfo;

	revealPassword = false;
	fixed = false;

	question1 = false;
	question2 = false;

	pagination = new Pagination();

	constructor(
		public app: AppService,
		public api: ApiService,
		public router: Router,
		public route: ActivatedRoute
	) { }

	ngOnInit() {
		this.pagination.pageSizeList = [10, 20, 50];
		this.pagination.pageSize = 10;

		this.sub = this.route.params.subscribe(params => {
			this.id = params['account'] || 0;
			this.token = params['token'] || '';
			this.refresh();
			this.scheduleKeepAlive();
		});
	}

	ngOnDestroy() {
		clearTimeout(this.keepAlive);
		this.sub.unsubscribe();
	}

	scheduleKeepAlive() {
		clearTimeout(this.keepAlive);
		this.keepAlive = setTimeout(() => {
			this.api.account.getDetails(this.id, this.token, () => {
				this.scheduleKeepAlive();
			}, () => {
				this.scheduleKeepAlive();
			});
		}, 300000);
	}

	refresh() {
		this.api.account.getDetails(this.id, this.token, response => {
			this.details = response.data;

			this.paymentMethodRequired = false;
			this.details.gateways.forEach(g => {
				if (g.type === 'stripe') {
					if (!g.has_card) this.paymentMethodRequired = true;
					g.amount_pence = this.details.outstanding_pence;
					g.amount = DecimalPipe.transform(this.details.outstanding_pence / 100, 2, 2, false);
					g.part_payment = false;
					if (!this.details.outstanding_pence || this.details.outstanding_pence < g.part_minimum_pence) g.allow_part_payment = 0;
				} else if (g.type === 'gocardless') {
					if (!g.has_mandate) this.paymentMethodRequired = true;
				}
			});

			this.signatureRequired = false;
			this.details.contracts.forEach(contract => {
				if (contract.has_pdf && !contract.is_pdf_signed) this.signatureRequired = true;
			});

			this.disabled = false;
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

	paySavedCard(gateway) {
		this.disabled = true;
		this.api.account.payBySavedCard(this.id, this.token, gateway.id, gateway.part_payment ? gateway.amount_pence : -1, response => {
			const stripe = Stripe(this.details.stripe_pk, {
				stripeAccount: response.data.stripe_user_id
			});
			stripe.confirmCardPayment(response.data.client_secret).then(result => {
				this.disabled = false;
				if (result.error) {
					this.app.notifications.showDanger(result.error.message);
				} else {
					if (result.paymentIntent.status === 'succeeded') {
						this.app.notifications.showSuccess('Payment successful!');

						// Successful payments are processed asynchronously via the Stripe webhook.
						// This makes sure the balance updates immediately, then we can do a full refresh in a couple of seconds.
						this.details.outstanding = 0;
						setTimeout(() => this.refresh(), 2000);
						return;
					}
				}
				this.refresh();
			});
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

	stripeCheckout(gateway) {
		this.disabled = true;
		this.api.account.addCard(this.id, this.token, gateway.id, gateway.part_payment ? gateway.amount_pence : -1, response => {
			const stripe = Stripe(this.details.stripe_pk, {
				stripeAccount: response.data.stripe_user_id
			});
			stripe.redirectToCheckout({ sessionId: response.data.checkout_session_id }).then(result => {
				this.disabled = false;
				if (result && result.error && result.error.message) {
					this.app.notifications.showDanger(result.error.message);
				}
			});
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

	setupDD(gateway) {
		this.disabled = true;
		this.api.account.getCustomerMandateUrl(this.id, this.token, gateway.id, response => {
			this.app.redirect(response.data);
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

	goToCloud() {
		this.app.redirect(this.app.getAppURL());
	}

	formatGatewayAmounts() {
		this.details.gateways.forEach(g => {
			g.amount_pence = Math.round(parseFloat(g.amount) * 100) || 0;
			if (g.amount_pence < g.part_minimum_pence) g.amount_pence = g.part_minimum_pence;
			if (g.amount_pence > this.details.outstanding_pence) g.amount_pence = this.details.outstanding_pence;

			g.amount = DecimalPipe.transform(g.amount_pence / 100, 2, 2, false);
		});
	}

	signPDF(c) {
		this.disabled = true;
		this.api.account.signContract({
			id: this.id,
			token: this.token,
			contract: c.id,
			name: this.signedBy
		}, () => {
			this.disabled = false;
			this.app.notifications.showSuccess('Contract signed.');
			this.refresh();
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

	selectCancelContract(c) {
		this.mode = 'cancel';
		this.cancelContract = c;
		this.cancelDate = null;
		this.cancelResult = null;
	}

	backToAccount() {
		this.mode = 'account';
		this.refresh();
	}

	checkCancelDate() {
		if (!this.cancelContract) return;

		const dt = MySQLDateToISOPipe.dateToString(this.cancelDate);

		this.cancelResult = null;
		this.disabled = true;
		this.api.account.checkCancelDate({
			id: this.id,
			token: this.token,
			contract: this.cancelContract.id,
			cancel_date: dt
		}, response => {
			this.disabled = false;
			this.cancelResult = response.data;
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

	submitCancellation() {
		if (!this.cancelContract) return;

		const dt = MySQLDateToISOPipe.dateToString(this.cancelDate);

		this.cancelResult = null;
		this.disabled = true;
		this.api.account.cancelContract({
			id: this.id,
			token: this.token,
			contract: this.cancelContract.id,
			cancel_date: dt
		}, () => {
			this.disabled = false;
			this.app.notifications.showSuccess('Cancellation request received.');
			this.backToAccount();
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

	selectUpgradeContract(c) {
		this.disabled = true;
		this.upgradeContract = null;
		this.upgradeInfo = null;
		this.upgradePackage = null;
		this.api.account.getUpgradeInfo({
			id: this.id,
			token: this.token,
			contract: c.id
		}, response => {
			this.disabled = false;
			this.upgradeInfo = response.data;

			if (!this.upgradeInfo.packages.length) {
				this.app.notifications.showSuccess('You are already on the largest package available.');
				return;
			}

			this.mode = 'upgrade';
			this.upgradeContract = c;
			this.upgradePackage = this.upgradeInfo.packages[0];
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

	upgradeBySavedCard(gateway) {
		this.disabled = true;
		this.api.account.upgradeBySavedCard(this.id, this.token, gateway.id, this.upgradeContract.id, this.upgradePackage.id, response => {
			const stripe = Stripe(this.details.stripe_pk, {
				stripeAccount: response.data.stripe_user_id
			});
			stripe.confirmCardPayment(response.data.client_secret).then(result => {
				this.disabled = false;
				if (result.error) {
					this.app.notifications.showDanger(result.error.message);
				} else {
					if (result.paymentIntent.status === 'succeeded') {
						// Successful payments are processed asynchronously via the Stripe webhook.
						// This makes sure the balance updates immediately, then we can do a full refresh in a couple of seconds.

						this.disabled = true;
						setTimeout(() => {
							this.disabled = false;
							this.app.notifications.showSuccess('Payment successful!');
							this.details.outstanding = 0;
							this.backToAccount();
						}, 5000);
						return;
					}
				}
				this.refresh();
			});
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

	upgradeByNewCard(gateway) {
		this.disabled = true;
		this.api.account.upgradeByNewCard(this.id, this.token, gateway.id, this.upgradeContract.id, this.upgradePackage.id, response => {
			const stripe = Stripe(this.details.stripe_pk, {
				stripeAccount: response.data.stripe_user_id
			});
			stripe.redirectToCheckout({ sessionId: response.data.checkout_session_id }).then(result => {
				this.disabled = false;
				if (result && result.error && result.error.message) {
					this.app.notifications.showDanger(result.error.message);
				}
			});
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

	selectSupportContract(c) {
		this.disabled = true;
		this.supportInfo = null;
		this.api.account.getSupportInfo({
			id: this.id,
			token: this.token,
			contract: c.id
		}, response => {
			this.disabled = false;
			this.mode = 'support';
			this.supportContract = c;
			this.supportInfo = response.data;
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

	fixMyInternet() {
		if (this.fixed) return;

		this.disabled = true;
		this.api.account.fixMyInternet({
			id: this.id,
			token: this.token,
			contract: this.supportContract.id
		}, () => {
			this.disabled = false;
			this.app.notifications.showSuccess('Request received.');
			this.fixed = true;
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

}
