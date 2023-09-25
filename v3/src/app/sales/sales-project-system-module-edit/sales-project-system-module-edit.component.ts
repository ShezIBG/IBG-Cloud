import { Location } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy, ViewChild } from '@angular/core';

declare var $: any;

@Component({
	selector: 'app-sales-project-system-module-edit',
	templateUrl: './sales-project-system-module-edit.component.html',
	styleUrls: ['./sales-project-system-module-edit.component.less']
})
export class SalesProjectSystemModuleEditComponent implements OnInit, OnDestroy {

	@ViewChild('fileInput') fileInput;

	id;
	data;
	disabled = false;
	draggedOver = false;
	hover = null;

	r = 0;
	g = 0;
	b = 0;

	get colour() { return this.data.details.colour; }
	set colour(value) {
		this.data.details.colour = value;
		this.calculateRGB();
	}

	private sub: any;

	constructor(
		private app: AppService,
		private api: ApiService,
		private route: ActivatedRoute,
		private location: Location
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			this.id = params['id'] || 'new';
			const owner = params['owner'];

			this.data = null;

			const success = response => {
				this.data = response.data || {};

				this.app.header.clearAll();
				this.app.header.addCrumbs(this.data.breadcrumbs);

				this.calculateRGB();
			}

			const fail = response => {
				this.app.notifications.showDanger(response.message);
			};

			if (this.id === 'new') {
				this.api.sales.newProjectModule(owner, success, fail);
			} else {
				this.api.sales.getProjectModule(this.id, success, fail);
			}
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

	calculateRGB() {
		const c = '' + this.data.details.colour;
		this.r = this.g = this.b = 0;

		if (c.length !== 7 || c[0] !== '#') return;

		this.r = parseInt(c.substr(1, 2), 16) || 0;
		this.g = parseInt(c.substr(3, 2), 16) || 0;
		this.b = parseInt(c.substr(5, 2), 16) || 0;
	}

	goBack() {
		this.location.back();
	}

	save() {
		this.disabled = true;
		this.api.sales.saveProjectModule(this.data.details, () => {
			this.disabled = false;
			this.goBack();
			if (this.id === 'new') {
				this.app.notifications.showSuccess('Project module created.');
			} else {
				this.app.notifications.showSuccess('Project module updated.');
			}
		}, response => {
			this.disabled = false;
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
			this.disabled = true;
			this.uploadFile(file, uc => {
				this.disabled = false;
				this.data.details.assets.push({
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
		const i = this.data.details.assets.indexOf(asset);
		if (i !== -1) {
			this.data.details.assets.splice(i, 1);
			this.data.details.assets.slice();
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
			this.data.details.assets.push({
				user_content_id: uc.id,
				url: uc.url
			});
		}, error => {
			this.disabled = false;
			this.app.notifications.showDanger(error);
		});
	}

}
