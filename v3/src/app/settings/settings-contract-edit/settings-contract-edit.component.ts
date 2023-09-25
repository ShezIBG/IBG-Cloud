import { Component, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { Location } from '@angular/common';
import { AppService } from 'app/app.service';
import { ApiService } from 'app/api.service';
import { ActivatedRoute } from '@angular/router';

declare var $: any;
declare var navigator: any;

@Component({
	selector: 'app-settings-contract-edit',
	templateUrl: './settings-contract-edit.component.html',
	styleUrls: ['./settings-contract-edit.component.less']
})
export class SettingsContractEditComponent implements OnInit, OnDestroy {

	@ViewChild('fileInput') fileInput;

	private sub: any;

	level;
	levelId;
	id;

	details;
	disabled = false;
	draggedOver = false;
	hover = null;

	tags = [
		{
			name: '{AREA_ADDRESS}',
			description: 'Address of the tenanted apartment.'
		},
		{
			name: '{CUSTOMER_ADDRESS}',
			description: 'Address on record for customer.'
		},
		{
			name: '{SIGNATURE}',
			description: 'Will be replaced by the customer\'s signature.'
		},
		{
			name: '{SIGNED_DATE}',
			description: 'The date the customer has signed the contract.'
		},
		{
			name: '{SIGNED_NAME}',
			description: 'Will be replaced by the customer\'s signed name.'
		}
	];

	constructor(
		public app: AppService,
		private api: ApiService,
		private route: ActivatedRoute,
		private location: Location
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			this.level = params['level'];
			this.levelId = params['levelId'];
			this.id = params['contract'];

			const success = response => {
				this.details = response.data.details;
			};

			const fail = response => {
				this.app.notifications.showDanger(response.message);
			};

			if (this.id === 'new') {
				this.api.settings.newContractTemplate(this.level, this.levelId, success, fail);
			} else {
				this.api.settings.getContractTemplate(this.level, this.levelId, this.id, success, fail);
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
		this.api.settings.saveContractTemplate(this.details, () => {
			this.disabled = false;
			this.goBack();
			this.app.notifications.showSuccess('Contract template saved.');
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

	deleteTemplate() {
		if (confirm('Are you sure you want to delete this contract template?')) {
			this.disabled = true;
			this.api.settings.deleteContractTemplate(this.level, this.levelId, this.id, () => {
				this.disabled = false;
				this.goBack();
				this.app.notifications.showSuccess('Contract template deleted.');
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

	copyTextToClipboard(text) {
		if (navigator.clipboard) {
			navigator.clipboard.writeText(text).then(() => {
				this.app.notifications.showSuccess('Tag copied to clipboard.');
			}, () => {
				this.app.notifications.showDanger('Cannot copy to clipboard.', 'Please copy the text manually.');
			});
		} else {
			// Fallback
			const textArea = document.createElement('textarea');
			textArea.value = text;
			document.body.appendChild(textArea);
			textArea.focus();
			textArea.select();

			try {
				if (document.execCommand('copy')) {
					this.app.notifications.showSuccess('Tag copied to clipboard.');
				} else {
					this.app.notifications.showDanger('Cannot copy to clipboard.', 'Please copy the text manually.');
				}
			} catch (err) {
				this.app.notifications.showDanger('Cannot copy to clipboard.', 'Please copy the text manually.');
			}

			document.body.removeChild(textArea);
		}
	}

}
