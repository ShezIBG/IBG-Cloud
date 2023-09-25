import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-isp-site-details',
	templateUrl: './isp-site-details.component.html'
})
export class IspSiteDetailsComponent implements OnInit, OnDestroy {

	details;

	private sub: any;

	constructor(
		public app: AppService,
		private api: ApiService,
		private route: ActivatedRoute
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			const baseRoute = '/isp/' + params['isp'] + '/site/' + params['building'];
			const tab = params['tab'];

			this.api.isp.getBuilding(params['building'], response => {
				this.details = response.data.details;

				this.app.header.clearAll();
				this.app.header.addCrumbs(response.data.breadcrumbs);
				this.app.header.addTab({ id: 'areas', title: 'Areas', route: baseRoute });
				this.app.header.addTab({ id: 'packages', title: 'Packages', route: baseRoute + '/packages' });
				this.app.header.addTab({ id: 'onu-types', title: 'ONU Types', route: baseRoute + '/onu-types' });
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
