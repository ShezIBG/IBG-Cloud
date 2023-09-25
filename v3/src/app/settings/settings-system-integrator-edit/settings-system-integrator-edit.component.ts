import { SortcodePipe } from './../../shared/sortcode.pipe';
import { AppService } from './../../app.service';
import { Location } from '@angular/common';
import { Router, ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { Component, OnInit, OnDestroy, ViewChild } from '@angular/core';

declare var $: any;

@Component({
	selector: 'app-settings-system-integrator-edit',
	templateUrl: './settings-system-integrator-edit.component.html',
	styleUrls: ['./settings-system-integrator-edit.component.less']
})
export class SettingsSystemIntegratorEditComponent implements OnInit, OnDestroy {

	@ViewChild('fileInputLight') fileInputLight;
	@ViewChild('fileInputDark') fileInputDark;

	private sub: any;

	id;
	details;
	disabled = false;

	imageUrlLight = null;
	imageUrlDark = null;
	draggedOver = false;

	constructor(
		private app: AppService,
		private api: ApiService,
		private router: Router,
		private route: ActivatedRoute,
		private location: Location
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			this.id = params['id'] || 'new';
			this.details = null;
			const level = params['level'];
			const levelId = params['levelId'];

			const success = response => {
				this.app.header.clearAll();
				this.app.header.addCrumbs(response.data.breadcrumbs);
				this.details = response.data.details || {};
				this.details.permissions = response.data.permissions;
				this.imageUrlLight = response.data.logo_on_light_url;
				this.imageUrlDark = response.data.logo_on_dark_url;

				this.formatSortCode();
			}

			const fail = response => {
				this.app.notifications.showDanger(response.message);
			};

			if (this.id === 'new') {
				this.api.settings.newSystemIntegrator(level, levelId, success, fail);
			} else {
				this.api.settings.getSystemIntegrator(this.id, success, fail);
			}
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

	formatSortCode() {
		this.details.bank_sort_code = SortcodePipe.transform(this.details.bank_sort_code);
	}

	goBack() {
		this.location.back();
	}

	save() {
		this.disabled = true;
		this.api.settings.saveSystemIntegrator(this.details, response => {
			this.disabled = false;
			if (this.details.id === 'new') {
				this.router.navigate(['/settings/system-integrator', response.data]);
				this.app.notifications.showSuccess('System integrator created.');
			} else {
				this.goBack();
				this.app.notifications.showSuccess('System integrator updated.');
			}
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

	copyAddress() {
		this.details.invoice_address_line_1 = this.details.address_line_1;
		this.details.invoice_address_line_2 = this.details.address_line_2;
		this.details.invoice_address_line_3 = this.details.address_line_3;
		this.details.invoice_posttown = this.details.posttown;
		this.details.invoice_postcode = this.details.postcode;
	}

	fileDragOver(ev, field) {
		this.draggedOver = field;
		ev.preventDefault();
	}

	fileDrop(ev, field) {
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
				if (field === 'light') {
					this.details.logo_on_light_id = uc.id;
					this.imageUrlLight = uc.url;
				} else {
					this.details.logo_on_dark_id = uc.id;
					this.imageUrlDark = uc.url;
				}
			}, error => {
				this.disabled = false;
				this.app.notifications.showDanger(error);
			});
		}
	}

	changeImage(field) {
		$((field === 'light' ? this.fileInputLight : this.fileInputDark).nativeElement).val('').click();
	}

	removeImage(field) {
		if (field === 'light') {
			this.details.logo_on_light_id = null;
			this.imageUrlLight = null;
		} else {
			this.details.logo_on_dark_id = null;
			this.imageUrlDark = null;
		}
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

	uploadImage(field) {
		this.disabled = true;
		this.uploadUserContent((field === 'light' ? this.fileInputLight : this.fileInputDark), uc => {
			this.disabled = false;
			if (field === 'light') {
				this.details.logo_on_light_id = uc.id;
				this.imageUrlLight = uc.url;
			} else {
				this.details.logo_on_dark_id = uc.id;
				this.imageUrlDark = uc.url;
			}
		}, error => {
			this.disabled = false;
			this.app.notifications.showDanger(error);
		});
	}

}
