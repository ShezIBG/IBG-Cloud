import { AppService } from './../../app.service';
import { ApiService } from './../../api.service';
import { Component, OnInit, Input } from '@angular/core';

@Component({
	selector: 'app-settings-emails',
	templateUrl: './settings-emails.component.html'
})
export class SettingsEmailsComponent implements OnInit {

	@Input() level;
	@Input() levelId;

	data: any = null;
	dirty = false;

	constructor(
		private api: ApiService,
		private app: AppService
	) { }

	ngOnInit() {
		this.refresh();
	}

	refresh() {
		this.api.settings.listEmailTemplates(this.level, this.levelId, response => {
			this.data = response.data;
			this.dirty = false;
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	setDirty() {
		this.dirty = true;
	}

	updateSMTP() {
		this.api.settings.updateSMTP(this.data.smtp, () => {
			this.app.notifications.showSuccess('SMTP settings updated.');
			this.refresh();
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

}
