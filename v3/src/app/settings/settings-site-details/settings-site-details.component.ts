import { AppService } from './../../app.service';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-settings-site-details',
	templateUrl: './settings-site-details.component.html'
})
export class SettingsSiteDetailsComponent implements OnInit, OnDestroy {

	private sub: any;

	id: any;
	details: any;
	systemIntegratorAdmin = false;
	configuratorUrl = '';

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
			const baseRoute = '/settings/site/' + this.id;
			this.api.settings.getBuilding(this.id, response => {
				this.details = response.data.details || {};
				this.systemIntegratorAdmin = !!response.data.system_integrator_admin;
				this.configuratorUrl = response.data.configurator_url || '';

				this.app.header.clearAll();
				this.app.header.addCrumbs(response.data.breadcrumbs);
				this.app.header.addTab({ id: 'overview', title: 'Overview', route: baseRoute });
				this.app.header.addTab({ id: 'users', title: 'Users', route: baseRoute + '/users' });
				this.app.header.addTab({ id: 'user-roles', title: 'User roles', route: baseRoute + '/user-roles' });
				this.app.header.setTab(tab);
			}, response => {
				this.app.notifications.showDanger(response.message);
			});
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

	openConfigurator() {
		window.open(this.configuratorUrl);
	}

}
