import { AppService } from './../../app.service';
import { ApiService } from './../../api.service';
import { Component, OnInit, Input } from '@angular/core';

@Component({
	selector: 'app-settings-users',
	templateUrl: './settings-users.component.html'
})
export class SettingsUsersComponent implements OnInit {

	@Input() level;
	@Input() levelId;

	list: any = [];
	count = { users: 0 };
	search = '';
	showNoAccess = false;

	constructor(
		private app: AppService,
		private api: ApiService
	) { }

	ngOnInit() {
		this.reloadList();
	}

	reloadList() {
		const success = response => {
			this.list = response.data.list || [];

			// Split area names if we're on building level
			if (this.level === 'B') {
				this.list.forEach(u => {
					if (u.area_description) {
						u.area_description = ('' + u.area_description).split(', ');
					} else {
						u.area_description = [];
					}
				});
			}
		};

		const fail = response => {
			this.app.notifications.showDanger(response.message);
		};

		if (this.level) {
			this.api.settings.listUsers(this.level, this.levelId, this.showNoAccess, success, fail);
		} else {
			this.api.settings.listAllUsers(this.showNoAccess, success, fail);
		}
	}

}
