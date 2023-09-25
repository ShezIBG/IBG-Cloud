import { AppService } from './../../app.service';
import { Location } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-settings-user-role-edit',
	templateUrl: './settings-user-role-edit.component.html'
})
export class SettingsUserRoleEditComponent implements OnInit, OnDestroy {

	private sub: any;

	id: any;
	level: any = '';
	levelId: any = '';
	details: any = null;
	ui: any = null;
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
			this.level = params['level'];
			this.levelId = params['levelId'];
			this.details = null;

			const success = response => {
				this.app.header.clearAll();
				this.app.header.addCrumbs(response.data.breadcrumbs);
				this.details = response.data.details || null;
				this.ui = response.data.ui || null;
			};

			const fail = response => {
				this.app.notifications.showDanger(response.message);
			};

			if (this.id === 'new') {
				this.api.settings.newUserRole(this.level, this.levelId, success, fail);
			} else {
				this.api.settings.getUserRole(this.id, success, fail);
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
		this.api.settings.saveUserRole(this.details, () => {
			this.disabled = false;
			this.goBack();
			this.app.notifications.showSuccess(this.id === 'new' ? 'User role created.' : 'User role updated.');
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

}
