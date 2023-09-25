import { Location } from '@angular/common';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';
import { ActivatedRoute } from '@angular/router';

@Component({
	selector: 'app-stock-smoothpower-edit',
	templateUrl: './stock-smoothpower-edit.component.html'
})
export class StockSmoothpowerEditComponent implements OnInit, OnDestroy {

	private sub: any;

	id;
	details;
	siList;
	disabled = false;

	constructor(
		private app: AppService,
		private api: ApiService,
		private route: ActivatedRoute,
		private location: Location
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			this.id = params['id'];
			this.details = null;

			this.api.smoothpower.getSmoothPowerUnit(this.id, response => {
				this.app.header.clearAll();
				this.app.header.addCrumbs(response.data.breadcrumbs);
				this.details = response.data.details || {};
				this.siList = response.data.si_list;
			}, response => {
				this.app.notifications.showDanger(response.message);
			});
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

	goBack() {
		this.location.back();
	}

	save() {
		this.disabled = true;
		this.api.smoothpower.saveSmoothPowerUnit(this.details, () => {
			this.disabled = false;
			this.goBack();
			this.app.notifications.showSuccess('SmoothPower unit updated.');
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

}
