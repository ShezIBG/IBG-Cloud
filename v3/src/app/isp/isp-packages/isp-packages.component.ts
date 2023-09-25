import { ActivatedRoute } from '@angular/router';
import { IspService } from './../isp.service';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-isp-packages',
	templateUrl: './isp-packages.component.html'
})
export class IspPackagesComponent implements OnInit, OnDestroy {

	list: any = null;
	single = false;

	private sub: any;

	constructor(
		private app: AppService,
		private api: ApiService,
		public isp: IspService,
		private route: ActivatedRoute
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			this.single = !!params['building'];
			this.api.isp.listPackages(params, response => {
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
