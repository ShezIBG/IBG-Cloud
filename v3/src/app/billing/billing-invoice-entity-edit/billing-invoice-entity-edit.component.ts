import { SortcodePipe } from './../../shared/sortcode.pipe';
import { Location } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy, ViewChild } from '@angular/core';

declare var $: any;

@Component({
	selector: 'app-billing-invoice-entity-edit',
	templateUrl: './billing-invoice-entity-edit.component.html',
	styleUrls: ['./billing-invoice-entity-edit.component.less']
})
export class BillingInvoiceEntityEditComponent implements OnInit, OnDestroy {

	@ViewChild('fileInput') fileInput;

	owner;
	details;
	archiveWarnings = [];
	disabled = false;

	imageUrl = null;
	draggedOver = false;

	private sub: any;

	constructor(
		private app: AppService,
		private api: ApiService,
		private route: ActivatedRoute,
		private location: Location
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			const id = params['entity'] || 'new';
			this.owner = params['owner'] || '';
			this.details = null;
			this.imageUrl = null;

			const success = response => {
				this.app.header.clearAll();
				this.app.header.addCrumbs(response.data.breadcrumbs);
				this.details = response.data.details || {};
				this.details.archived = !!this.details.archived;
				this.imageUrl = response.data.image_url;
				this.archiveWarnings = response.data.archive_warnings || [];

				this.formatSortCode();
			}

			const fail = response => {
				this.app.notifications.showDanger(response.message);
			};

			if (id === 'new') {
				this.api.billing.newInvoiceEntity(this.owner, success, fail);
			} else {
				this.api.billing.getInvoiceEntity(this.owner, id, success, fail);
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
		this.api.billing.saveInvoiceEntity(this.details, response => {
			this.disabled = false;
			if (this.details.id === 'new') {
				this.app.notifications.showSuccess('Invoicing entity created.');
			} else {
				this.app.notifications.showSuccess('Invoicing entity updated.');
			}
			this.goBack();
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

	formatSortCode() {
		this.details.bank_sort_code = SortcodePipe.transform(this.details.bank_sort_code);
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
