import { ApiService } from './../../api.service';
import { Component, OnInit, Input } from '@angular/core';

@Component({
	selector: 'app-settings-holding-groups',
	templateUrl: './settings-holding-groups.component.html'
})
export class SettingsHoldingGroupsComponent implements OnInit {

	@Input() level;
	@Input() levelId;

	list: any = [];
	count = { hg: 0 };
	search = '';

	constructor(private api: ApiService) { }

	ngOnInit() {
		const success = response => {
			this.list = response.data.list || [];
		};

		if (this.level) {
			this.api.settings.listHoldingGroups(this.level, this.levelId, success);
		} else {
			this.api.settings.listAllHoldingGroups(success);
		}
	}

}
