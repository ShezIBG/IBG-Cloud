import { ApiService } from './../../api.service';
import { Component, OnInit, Input } from '@angular/core';

@Component({
	selector: 'app-settings-user-roles',
	templateUrl: './settings-user-roles.component.html'
})
export class SettingsUserRolesComponent implements OnInit {

	@Input() level;
	@Input() levelId;

	list: any = [];
	count = { roles: 0 };
	search = '';

	constructor(private api: ApiService) { }

	ngOnInit() {
		if (this.level) {
			this.api.settings.listUserRoles(this.level, this.levelId, response => {
				this.list = response.data.list || [];
			});
		}
	}

}
