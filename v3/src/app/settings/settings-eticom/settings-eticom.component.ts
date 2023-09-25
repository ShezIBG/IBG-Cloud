import { AppService } from './../../app.service';
import { ApiService } from './../../api.service';
import { ActivatedRoute } from '@angular/router';
import { Component, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-settings-eticom',
	templateUrl: './settings-eticom.component.html'
})
export class SettingsEticomComponent implements OnInit, OnDestroy {

	public sub;

	loaded = false;

	constructor(
		public app: AppService,
		private api: ApiService,
		private route: ActivatedRoute
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			const tab = params['tab'];
			const baseRoute = '/settings/eticom'
			this.api.settings.getEticom(response => {
				this.loaded = true;

				this.app.header.clearAll();
				this.app.header.addCrumbs(response.data.breadcrumbs);
				this.app.header.addTab({ id: 'service-providers', title: 'Service Providers', route: baseRoute + '/service-providers' });
				this.app.header.addTab({ id: 'users', title: 'Users', route: baseRoute + '/users' });
				this.app.header.addTab({ id: 'user-roles', title: 'User roles', route: baseRoute + '/user-roles' });
				// TODO: Payment gateways for Eticom?
				// this.app.header.addTab({ id: 'payment-gateways', title: 'Payment gateways', route: baseRoute + '/payment-gateways' });
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
