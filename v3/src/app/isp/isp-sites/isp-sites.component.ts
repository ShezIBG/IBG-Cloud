import { ActivatedRoute } from '@angular/router';
import { IspService } from './../isp.service';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-isp-sites',
	templateUrl: './isp-sites.component.html'
})
export class IspSitesComponent implements OnInit, OnDestroy {

	list: any = null;
	count = { sites: 0 };
	search = '';

	private sub: any;

	constructor(
		private app: AppService,
		private api: ApiService,
		public isp: IspService,
		private route: ActivatedRoute
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			this.api.isp.listBuildings(params, response => {
				this.list = response.data;
			}, response => {
				this.app.notifications.showDanger(response.message);
			});
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

}
