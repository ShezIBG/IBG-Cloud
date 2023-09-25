import { AppService } from './../../app.service';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-settings-system-integrator-details',
	templateUrl: './settings-system-integrator-details.component.html'
})
export class SettingsSystemIntegratorDetailsComponent implements OnInit, OnDestroy {

	private sub: any;

	id: any;
	details: any;
	isp = false;
	paymentAccounts = [];

	Math = Math;

	constructor(
		public app: AppService,
		private api: ApiService,
		private route: ActivatedRoute
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			this.id = params['id'];
			this.details = {};
			const tab = params['tab'];
			const baseRoute = '/settings/system-integrator/' + this.id;
			this.api.settings.getSystemIntegrator(this.id, response => {
				this.details = response.data.details || {};
				this.isp = response.data.isp;
				this.paymentAccounts = response.data.payment_accounts || [];

				this.app.header.clearAll();
				this.app.header.addCrumbs(response.data.breadcrumbs);
				this.app.header.addTab({ id: 'overview', title: 'Overview', route: baseRoute });
				this.app.header.addTab({ id: 'holding-groups', title: 'Holding Groups', route: baseRoute + '/holding-groups' });
				this.app.header.addTab({ id: 'clients', title: 'Clients', route: baseRoute + '/clients' });
				this.app.header.addTab({ id: 'users', title: 'Users', route: baseRoute + '/users' });
				this.app.header.addTab({ id: 'user-roles', title: 'User roles', route: baseRoute + '/user-roles' });
				this.app.header.addTab({ id: 'payment-gateways', title: 'Payment gateways', route: baseRoute + '/payment-gateways' });
				if (this.isp) this.app.header.addTab({ id: 'emails', title: 'Emails', route: baseRoute + '/emails' });
				if (this.isp) this.app.header.addTab({ id: 'contracts', title: 'Contracts', route: baseRoute + '/contracts' });
				this.app.header.setTab(tab);
			}, response => {
				this.app.notifications.showDanger(response.message);
			});
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

}
