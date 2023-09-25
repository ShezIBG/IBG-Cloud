import { AppService } from './../../app.service';
import { ApiService } from './../../api.service';
import { ActivatedRoute } from '@angular/router';
import { Component, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-isp-client-details',
	templateUrl: './isp-client-details.component.html'
})
export class IspClientDetailsComponent implements OnInit, OnDestroy {

	details;

	private sub: any;

	constructor(
		public app: AppService,
		private api: ApiService,
		private route: ActivatedRoute
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			const baseRoute = '/isp/' + params['isp'] + '/client/' + params['client'];
			const tab = params['tab'];

			this.api.isp.getClient(params['client'], response => {
				this.details = response.data.details;

				this.app.header.clearAll();
				this.app.header.addCrumbs(response.data.breadcrumbs);
				this.app.header.addTab({ id: 'sites', title: 'Sites', route: baseRoute });
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
