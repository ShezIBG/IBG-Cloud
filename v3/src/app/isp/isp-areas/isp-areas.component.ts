import { ActivatedRoute } from '@angular/router';
import { IspService } from './../isp.service';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

declare var Mangler: any;

@Component({
	selector: 'app-isp-areas',
	templateUrl: './isp-areas.component.html'
})
export class IspAreasComponent implements OnInit, OnDestroy {

	isp_id = null;
	lastParams;
	list: any = null;
	count = { areas: 0 };
	search = '';
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
			this.isp_id = params['isp'] || null;
			this.lastParams = params;
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

		this.api.isp.listAreas(this.lastParams, response => {
			if (this.destroyed) return;

			this.list = response.data.areas;

			this.list.forEach(area => {
				area.customers = [];
			});

			const areaIndex = Mangler.index(this.list, 'id');
			response.data.onus.forEach(onu => {
				const area = areaIndex[onu.area_id];
				if (area && !area.onu) {
					area.onu = onu;
				}
			});

			response.data.customers.forEach(customer => {
				const name = [];
				if (customer.contact_name) name.push(customer.contact_name);
				if (customer.company_name) name.push(customer.company_name);
				customer.customer_name = name.join(', ');

				const area = areaIndex[customer.area_id];
				if (area) area.customers.push(customer);
			});

			this.timer = setTimeout(() => this.refresh(), 30000);
		}, response => {
			if (this.destroyed) return;
			this.app.notifications.showDanger(response.message);
			this.timer = setTimeout(() => this.refresh(), 30000);
		});
	}

}
