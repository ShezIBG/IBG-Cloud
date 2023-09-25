import { ActivatedRoute } from '@angular/router';
import { IspService } from './../isp.service';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-isp-overview',
	templateUrl: './isp-overview.component.html'
})
export class IspOverviewComponent implements OnInit, OnDestroy {

	isp_id;
	overview;
	timer;
	destroyed;

	private sub: any;

	constructor(
		private app: AppService,
		private api: ApiService,
		public isp: IspService,
		private route: ActivatedRoute
	) { }

	ngOnInit() {
		this.destroyed = false;
		this.sub = this.route.params.subscribe(params => {
			this.isp_id = params['isp'];
			this.refresh();
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
		this.destroyed = true;
		clearTimeout(this.timer);
	}

	refresh() {
		clearTimeout(this.timer);

		this.api.isp.getOverview(this.isp_id, response => {
			if (this.destroyed) return;
			this.overview = response.data;
			this.timer = setTimeout(() => this.refresh(), 30000);
		}, response => {
			if (this.destroyed) return;
			this.app.notifications.showDanger(response.message);
			this.timer = setTimeout(() => this.refresh(), 30000);
		});
	}

}
