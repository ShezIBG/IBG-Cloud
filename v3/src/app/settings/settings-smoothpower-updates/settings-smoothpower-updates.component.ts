import { AppService } from './../../app.service';
import { ApiService } from './../../api.service';
import { Component, OnInit, ViewChild } from '@angular/core';

@Component({
	selector: 'app-settings-smoothpower-updates',
	templateUrl: './settings-smoothpower-updates.component.html',
	styleUrls: ['./settings-smoothpower-updates.component.less']
})
export class SettingsSmoothpowerUpdatesComponent implements OnInit {

	@ViewChild('fileInput') fileInput;

	channels: any = [];
	list: any = {};
	draggedOver = false;
	releasedVersions = [];

	constructor(
		private app: AppService,
		private api: ApiService
	) { }

	ngOnInit() {
		this.reloadUpdates();
	}

	reloadUpdates() {
		this.api.settings.listSmoothPowerUpdates(response => {
			this.channels = response.data.channels;
			this.list = response.data.list;

			this.releasedVersions = [];
			this.list.release.forEach(p => {
				this.releasedVersions.push(p.version);
			});
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	fileDragOver(ev) {
		this.draggedOver = true;
		ev.preventDefault();
	}

	fileDrop(ev) {
		this.draggedOver = false;
		ev.preventDefault();

		// If dropped items aren't files, reject them
		const dt = ev.dataTransfer;
		let file = null;
		if (dt.items) {
			// Use DataTransferItemList interface to access the file(s)
			if (dt.items.length) file = dt.items[0].getAsFile();
		} else {
			// Use DataTransfer interface to access the file(s)
			if (dt.files.length) file = dt.files[0];
		}

		if (file) {
			this.uploadFile(file, response => {
				this.reloadUpdates();
			}, error => {
				this.app.notifications.showDanger(error);
			});
		}
	}

	uploadFile(file, success, failure) {
		const formData = new FormData();
		formData.append('userfile', file);

		this.api.general.uploadSmoothPowerUpdate(formData, res => {
			try {
				if (res.data.errors && res.data.errors[0]) {
					failure(res.data.errors[0]);
				} else {
					if (res.data.files[0].id) success(res.data.files[0]);
				}
			} catch (ex) {
				failure('No file uploaded.');
			}
		}, response => {
			failure(response.message);
		});
	}

	uploadUserContent(fileElement, success, failure) {
		if (!fileElement) {
			failure('No file uploaded.');
			return;
		}

		const fileBrowser = fileElement.nativeElement;
		if (fileBrowser.files && fileBrowser.files[0]) {
			this.uploadFile(fileBrowser.files[0], success, failure);
		} else {
			failure('No file uploaded.');
			return;
		}
	}

	uploadPackage() {
		this.uploadUserContent(this.fileInput, () => {
			this.app.notifications.showSuccess('Package uploaded to test channel.');
			this.reloadUpdates();
		}, error => {
			this.app.notifications.showDanger(error);
		});
	}

	setRollback(id, value) {
		if (confirm('Toggle rollback state?')) {
			this.api.settings.setSmoothPowerRollback(id, value, () => {
				this.app.notifications.showSuccess('Rollback state updated.');
				this.reloadUpdates();
			}, response => {
				this.app.notifications.showDanger(response.message);
			});
		}
	}

	setChannel(id, value) {
		if (confirm('Push update to ' + value + ' channel?')) {
			this.api.settings.setSmoothPowerChannel(id, value, () => {
				this.app.notifications.showSuccess('Packaged pushed to ' + value + ' channel.');
				this.reloadUpdates();
			}, response => {
				this.app.notifications.showDanger(response.message);
			});
		}
	}

	deletePackage(id) {
		if (confirm('Delete package?')) {
			this.api.settings.deleteSmoothPowerUpdate(id, () => {
				this.app.notifications.showSuccess('Packaged deleted.');
				this.reloadUpdates();
			}, response => {
				this.app.notifications.showDanger(response.message);
			});
		}
	}

	isVersionReleased(version) {
		return this.releasedVersions.indexOf(version) !== -1;
	}

}
