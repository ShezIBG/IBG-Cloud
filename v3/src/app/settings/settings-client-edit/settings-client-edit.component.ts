import { SortcodePipe } from './../../shared/sortcode.pipe';
import { AppService } from './../../app.service';
import { Location } from '@angular/common';
import { Router, ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { Component, OnInit, OnDestroy, ViewChild } from '@angular/core';

declare var $: any;

@Component({
	selector: 'app-settings-client-edit',
	templateUrl: './settings-client-edit.component.html',
	styleUrls: ['./settings-client-edit.component.less']
})
export class SettingsClientEditComponent implements OnInit, OnDestroy {

	@ViewChild('fileInput') fileInput;

	private sub: any;

	id;
	details;
	disabled = false;

	imageUrl = null;
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
			this.imageUrl = null;
			const level = params['level'];
			const levelId = params['levelId'];

			const success = response => {
				this.app.header.clearAll();
				this.app.header.addCrumbs(response.data.breadcrumbs);
				this.details = response.data.details || {};
				this.details.permissions = response.data.permissions;
				this.imageUrl = response.data.image_url;

				this.formatSortCode();
			}

			const fail = response => {
				this.app.notifications.showDanger(response.message);
			};

			if (this.id === 'new') {
				this.api.settings.newClient(level, levelId, success, fail);
			} else {
				this.api.settings.getClient(this.id, success, fail);
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
		this.api.settings.saveClient(this.details, response => {
			this.disabled = false;
			if (this.details.id === 'new') {
				this.router.navigate(['/settings/client', response.data]);
				this.app.notifications.showSuccess('Client created.');
			} else {
				this.goBack();
				this.app.notifications.showSuccess('Client updated.');
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
