import { AppService } from './../../app.service';
import { ApiService } from './../../api.service';
import { Component, OnInit } from '@angular/core';

@Component({
	selector: 'app-settings-user-profile',
	template: `<app-settings-user-edit *ngIf="userId" [userId]="userId"></app-settings-user-edit>`
})
export class SettingsUserProfileComponent implements OnInit {

	userId = null;

	constructor(
		private app: AppService,
		private api: ApiService
	) { }

	ngOnInit() {
		this.api.settings.getCurrentUserId(response => {
			this.userId = response.data;
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

}
