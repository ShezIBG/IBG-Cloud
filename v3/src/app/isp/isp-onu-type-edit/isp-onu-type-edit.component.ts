import { Location } from '@angular/common';
import { Component, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from 'app/api.service';
import { AppService } from 'app/app.service';

declare var $: any;

@Component({
	selector: 'app-isp-onu-type-edit',
	templateUrl: './isp-onu-type-edit.component.html',
	styleUrls: ['./isp-onu-type-edit.component.less']
})
export class IspOnuTypeEditComponent implements OnInit, OnDestroy {

	@ViewChild('fileInput') fileInput;

	buildingId;
	typeId;

	details;
	canDelete = false;
	disabled = false;
	draggedOver = false;
	hover = null;

	private sub: any;

	constructor(
		public app: AppService,
		private api: ApiService,
		private route: ActivatedRoute,
		private location: Location
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			this.typeId = params['onutype'] || 'new';
			this.buildingId = params['building'] || '';

			const success = response => {
				this.details = response.data.details;
				this.canDelete = response.data.can_delete;

				this.app.header.clearAll();
				this.app.header.addCrumbs(response.data.breadcrumbs);
			};

			const fail = response => {
				this.app.notifications.showDanger(response.message);
			}

			if (this.typeId === 'new') {
				this.api.isp.newOnuType(this.buildingId, success, fail);
			} else {
				this.api.isp.getOnuType(this.typeId, success, fail);
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
		this.api.isp.saveOnuType(this.details, () => {
			this.disabled = false;
			this.goBack();
			this.app.notifications.showSuccess('ONU type saved.');
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

	deleteOnuType() {
		if(!this.canDelete) return;

		if (confirm('Are you sure you want to delete this ONU type?')) {
			this.disabled = true;
			this.api.isp.deleteOnuType(this.typeId, () => {
				this.disabled = false;
				this.goBack();
				this.app.notifications.showSuccess('ONU type deleted.');
			}, response => {
				this.disabled = false;
				this.app.notifications.showDanger(response.message);
			});
		}
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
				this.details.assets.push({
					user_content_id: uc.id,
					url: uc.url
				});
			}, error => {
				this.disabled = false;
				this.app.notifications.showDanger(error);
			});
		}
	}

	changeImage() {
		$(this.fileInput.nativeElement).val('').click();
	}

	deleteAsset(asset) {
		const i = this.details.assets.indexOf(asset);
		if (i !== -1) {
			this.details.assets.splice(i, 1);
			this.details.assets.slice();
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

	uploadImage() {
		this.disabled = true;
		this.uploadUserContent(this.fileInput, uc => {
			this.disabled = false;
			this.details.assets.push({
				user_content_id: uc.id,
				url: uc.url
			});
		}, error => {
			this.disabled = false;
			this.app.notifications.showDanger(error);
		});
	}

}
