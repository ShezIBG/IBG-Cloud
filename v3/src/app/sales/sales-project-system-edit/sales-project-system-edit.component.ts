import { Location } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-sales-project-system-edit',
	templateUrl: './sales-project-system-edit.component.html'
})
export class SalesProjectSystemEditComponent implements OnInit, OnDestroy {

	id;
	moduleId;
	data;
	disabled = false;

	private sub: any;

	constructor(
		private app: AppService,
		private api: ApiService,
		private route: ActivatedRoute,
		private location: Location
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			this.id = params['id'] || 'new';
			const owner = params['owner'];

			this.moduleId = parseInt(params['moduleId'], 10) || null;
			this.data = null;

			const success = response => {
				this.data = response.data || {};

				if (this.moduleId) this.data.details.module_id = this.moduleId;

				this.app.header.clearAll();
				this.app.header.addCrumbs(this.data.breadcrumbs);
			}

			const fail = response => {
				this.app.notifications.showDanger(response.message);
			};

			if (this.id === 'new') {
				this.api.sales.newProjectSystem(owner, success, fail);
			} else {
				this.api.sales.getProjectSystem(this.id, success, fail);
			}
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
		this.api.sales.saveProjectSystem(this.data.details, () => {
			this.disabled = false;
			this.goBack();
			if (this.id === 'new') {
				this.app.notifications.showSuccess('System created.');
			} else {
				this.app.notifications.showSuccess('System updated.');
			}
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

}
