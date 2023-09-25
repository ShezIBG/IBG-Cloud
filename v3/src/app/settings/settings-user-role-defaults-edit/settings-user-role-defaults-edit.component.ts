import { Location } from '@angular/common';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit } from '@angular/core';

declare var Mangler: any;

@Component({
	selector: 'app-settings-user-role-defaults-edit',
	templateUrl: './settings-user-role-defaults-edit.component.html',
	styleUrls: ['./settings-user-role-defaults-edit.component.less']
})
export class SettingsUserRoleDefaultsEditComponent implements OnInit {

	details: any;
	disabled = false;
	levels: any[] = [];
	levelIndex: any = {};

	constructor(
		private app: AppService,
		private api: ApiService,
		private location: Location
	) { }

	ngOnInit() {
		this.api.settings.getUserRoleDefaults(response => {
			this.app.header.clearAll();
			this.app.header.addCrumbs(response.data.breadcrumbs);
			this.levels = response.data.levels || null;
			this.details = response.data.details || null;

			this.levelIndex = Mangler.index(this.levels, 'id');
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	goBack() {
		this.location.back();
	}

	save() {
		this.disabled = true;
		this.api.settings.saveUserRoleDefaults({ details: this.details, levels: this.levels }, () => {
			this.disabled = false;
			this.goBack();
			this.app.notifications.showSuccess('Permission levels updated.');
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

	filterState(level, perm) {
		const record = this.levelIndex[level];
		return record && !!(record['filter'][perm.field] & perm.flag);
	}

	filterToggle(level, perm) {
		const record = this.levelIndex[level];
		if (record) record['filter'][perm.field] ^= perm.flag;
	}

}
