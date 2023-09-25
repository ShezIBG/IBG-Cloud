import { AppService } from './../../app.service';
import { Location } from '@angular/common';
import { Router, ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { Component, OnInit, OnDestroy, ViewChild } from '@angular/core';

declare var $: any;

@Component({
	selector: 'app-settings-site-edit',
	templateUrl: './settings-site-edit.component.html',
	styleUrls: ['./settings-site-edit.component.less']
})
export class SettingsSiteEditComponent implements OnInit, OnDestroy {

	@ViewChild('fileInput') fileInput;

	private sub: any;

	id;
	details;
	categories = [];
	disabled = false;

	imageUrl = null;
	draggedOver = false;
	systemIntegratorAdmin = false;

	get allowReport() { return !!this.details.allow_report; }
	set allowReport(value) { this.details.allow_report = value ? 1 : 0; }

	constructor(
		public app: AppService,
		private api: ApiService,
		private router: Router,
		private route: ActivatedRoute,
		private location: Location
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			this.id = params['id'] || 'new';
			this.details = null;
			this.categories = [];
			const level = params['level'];
			const levelId = params['levelId'];

			const success = response => {
				this.details = response.data.details || {};
				this.imageUrl = response.data.image_url;
				this.categories = response.data.categories || [];
				this.details.permissions = response.data.permissions;
				this.systemIntegratorAdmin = !!response.data.system_integrator_admin;

				this.app.header.clearAll();
				this.app.header.addCrumbs(response.data.breadcrumbs);

				this.app.header.clearTabs();
				this.app.header.addTab({ id: 'details', title: 'Site details' });
				if (this.details.module_electricity === 1) this.app.header.addTab({ id: 'dashboard', title: 'Dashboard settings' });
			}

			const fail = response => {
				this.app.notifications.showDanger(response.message);
			};

			if (this.id === 'new') {
				this.api.settings.newBuilding(level, levelId, success, fail);
			} else {
				this.api.settings.getBuilding(this.id, success, fail);
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
		this.api.settings.saveBuilding(this.details, response => {
			this.disabled = false;
			if (this.details.id === 'new') {
				this.router.navigate(['/settings/site', response.data]);
				this.app.notifications.showSuccess('Site created.');
			} else {
				this.goBack();
				this.app.notifications.showSuccess('Site updated.');
			}
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

	isCategoryHidden(item) {
		if (!this.details || !this.details.hidden_categories) return false;

		const i = this.details.hidden_categories.indexOf(item.id);
		return i !== -1;
	}

	toggleCategory(item) {
		if (!this.details || !this.details.hidden_categories) return false;

		const i = this.details.hidden_categories.indexOf(item.id);
		if (i === -1) {
			this.details.hidden_categories.push(item.id);
		} else {
			this.details.hidden_categories.splice(i, 1);
		}

		this.details.hidden_categories = this.details.hidden_categories.slice();
	}

	locationChanged(location) {
		this.details.latitude = location.lat;
		this.details.longitude = location.lng;
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
			this.disabled = true;
			this.uploadFile(file, uc => {
				this.disabled = false;
				this.details.image_id = uc.id;
				this.imageUrl = uc.url;
			}, error => {
				this.disabled = false;
				this.app.notifications.showDanger(error);
			});
		}
	}

	changeImage() {
		$(this.fileInput.nativeElement).val('').click();
	}

	removeImage() {
		this.details.image_id = null;
		this.imageUrl = null;
	}

	uploadFile(file, success, failure) {
		const formData = new FormData();
		formData.append('userfile', file);

		this.api.general.uploadImage(formData, 0, 0, res => {
			try {
				const resFile = res.data.files[0];
				const uc = {
					id: resFile.id,
					url: resFile.url
				};
				success(uc);
			} catch (ex) {
				failure('No file uploaded.');
			}
		}, () => {
			failure('No file uploaded.');
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

	uploadImage() {
		this.disabled = true;
		this.uploadUserContent(this.fileInput, uc => {
			this.disabled = false;
			this.details.image_id = uc.id;
			this.imageUrl = uc.url;
		}, error => {
			this.disabled = false;
			this.app.notifications.showDanger(error);
		});
	}

}
