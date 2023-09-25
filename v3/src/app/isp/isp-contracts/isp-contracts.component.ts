import { ActivatedRoute } from '@angular/router';
import { IspService } from './../isp.service';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy, Input } from '@angular/core';
import { Pagination } from 'app/shared/pagination';

@Component({
	selector: 'app-isp-contracts',
	templateUrl: './isp-contracts.component.html'
})
export class IspContractsComponent implements OnInit, OnDestroy {

	@Input() hideNew = false;

	isp_id;
	customer;
	list: any = null;
	templates: any = null;

	count = {
		templates: 0,
	};
	search = '';
	pagination = new Pagination();

	private sub: any;

	constructor(
		private app: AppService,
		private api: ApiService,
		public isp: IspService,
		private route: ActivatedRoute
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			this.customer = params['customer'];
			this.isp_id = params['isp'];
			this.api.isp.listContracts(params, response => {
				this.list = response.data;
			}, response => {
				this.app.notifications.showDanger(response.message);
			});

			this.api.isp.listContractTemplates(params, response => {
				this.templates = response.data;
			});
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

}
