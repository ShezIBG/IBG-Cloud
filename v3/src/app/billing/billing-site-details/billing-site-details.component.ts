import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-billing-site-details',
	templateUrl: './billing-site-details.component.html'
})
export class BillingSiteDetailsComponent implements OnInit, OnDestroy {

	details;

	private sub: any;

	constructor(
		public app: AppService,
		private api: ApiService,
		private route: ActivatedRoute
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			const baseRoute = '/billing/' + params['owner'] + '/site/' + params['building'];
			const tab = params['tab'];

			this.api.billing.getBuilding(params['owner'], params['building'], response => {
				this.details = response.data.details;

				this.app.header.clearAll();
				this.app.header.addCrumbs(response.data.breadcrumbs);
				this.app.header.addTab({ id: 'areas', title: 'Areas', route: baseRoute });
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
