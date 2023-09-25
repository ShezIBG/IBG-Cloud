import { Component, OnDestroy, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { ApiService } from 'app/api.service';
import { AppService } from 'app/app.service';
import { IspService } from '../isp.service';

@Component({
	selector: 'app-isp-onu-types',
	templateUrl: './isp-onu-types.component.html'
})
export class IspOnuTypesComponent implements OnInit, OnDestroy {

	buildingId;

	list: any = null;
	buildings;
	copyFromBuilding = null;
	disabled = false;

	private sub: any;

	constructor(
		private app: AppService,
		private api: ApiService,
		public isp: IspService,
		private route: ActivatedRoute,
		private router: Router
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			this.buildingId = params['building'];
			this.refresh();
		});
	}

	refresh() {
		this.api.isp.listOnuTypes(this.buildingId, response => {
			this.list = response.data.list;
			this.buildings = response.data.buildings;
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

	createOnuType() {
		this.router.navigate(['/isp', this.isp.id, 'onu-type', 'new', this.buildingId]);
	}

	copyOnuTypes() {
		this.disabled = true;
		this.api.isp.copyOnuTypes(this.copyFromBuilding, this.buildingId, () => {
			this.disabled = false;
			this.app.notifications.showSuccess('ONU types copied successfully.');
			this.refresh();
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

}
