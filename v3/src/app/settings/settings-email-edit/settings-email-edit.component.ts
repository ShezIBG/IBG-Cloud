import { Location } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy, ViewChild } from '@angular/core';

declare var $: any;
declare var navigator: any;

@Component({
	selector: 'app-settings-email-edit',
	templateUrl: './settings-email-edit.component.html',
	styleUrls: ['./settings-email-edit.component.less']
})
export class SettingsEmailEditComponent implements OnInit, OnDestroy {

	@ViewChild('fileInput') fileInput;

	private sub: any;

	level;
	levelId;
	templateType;

	details;
	disabled = false;
	draggedOver = false;
	hover = null;

	tags = [
		{
			name: '{LINK}',
			description: 'Clickable link to the customer\'s payment page.',
			hidden: false,
			templates: ['isp_not_signed', 'isp_welcome', 'isp_welcome_dd', 'isp_activate', 'isp_invoice', 'isp_invoice_dd', 'isp_dd_fail', 'isp_dd_fail_card', 'isp_card_fail', 'isp_card_expires', 'isp_card_expired', 'isp_dd_cancelled', 'custom_1', 'custom_2', 'custom_3', 'custom_4', 'custom_5', 'custom_6', 'custom_7', 'custom_8', 'custom_9', 'custom_10']
		},
		{
			name: '{URL}',
			description: 'URL for the above. Can be used in HTML code to customise the link description.',
			hidden: false,
			templates: ['isp_not_signed', 'isp_welcome', 'isp_welcome_dd', 'isp_activate', 'isp_invoice', 'isp_invoice_dd', 'isp_dd_fail', 'isp_dd_fail_card', 'isp_card_fail', 'isp_card_expires', 'isp_card_expired', 'isp_dd_cancelled', 'custom_1', 'custom_2', 'custom_3', 'custom_4', 'custom_5', 'custom_6', 'custom_7', 'custom_8', 'custom_9', 'custom_10']
		},
		{
			name: '{OUTSTANDINGAMOUNT}',
			description: 'Customer\'s total outstanding amount.',
			hidden: false,
			templates: ['isp_welcome', 'isp_welcome_dd', 'isp_activate', 'isp_invoice', 'isp_invoice_dd', 'isp_dd_fail', 'isp_dd_fail_card', 'isp_card_fail', 'isp_card_expires', 'isp_card_expired', 'isp_dd_cancelled', 'custom_1', 'custom_2', 'custom_3', 'custom_4', 'custom_5', 'custom_6', 'custom_7', 'custom_8', 'custom_9', 'custom_10']
		},
		{
			name: '{CUSTOMERNAME}',
			description: 'Customer name',
			hidden: false,
			templates: ['isp_not_signed', 'isp_welcome', 'isp_welcome_dd', 'isp_activate', 'isp_invoice', 'isp_invoice_dd', 'isp_dd_fail', 'isp_dd_fail_card', 'isp_card_fail', 'isp_card_expires', 'isp_card_expired', 'isp_dd_cancelled', 'custom_1', 'custom_2', 'custom_3', 'custom_4', 'custom_5', 'custom_6', 'custom_7', 'custom_8', 'custom_9', 'custom_10']
		},
		{
			name: '{PACKAGE}',
			description: 'ISP package name selected in the contract.',
			hidden: false,
			templates: ['isp_not_signed', 'isp_welcome', 'isp_welcome_dd', 'isp_activate', 'isp_invoice', 'isp_invoice_dd', 'isp_dd_fail', 'isp_dd_fail_card', 'isp_card_fail', 'isp_card_expires', 'isp_card_expired', 'isp_dd_cancelled']
		},
		{
			name: '{WIFISSID}',
			description: 'Wi-Fi SSID of the customer\'s apartment.',
			hidden: false,
			templates: ['isp_not_signed', 'isp_welcome', 'isp_welcome_dd', 'isp_activate', 'isp_invoice', 'isp_invoice_dd', 'isp_dd_fail', 'isp_dd_fail_card', 'isp_card_fail', 'isp_card_expires', 'isp_card_expired', 'isp_dd_cancelled']
		},
		{
			name: '{WIFIPASSWORD}',
			description: 'Wi-Fi password for the customer\'s apartment.',
			hidden: false,
			templates: ['isp_not_signed', 'isp_welcome', 'isp_welcome_dd', 'isp_activate', 'isp_invoice', 'isp_invoice_dd', 'isp_dd_fail', 'isp_dd_fail_card', 'isp_card_fail', 'isp_card_expires', 'isp_card_expired', 'isp_dd_cancelled']
		},
		{
			name: '{INVOICEDATE}',
			description: 'Invoice issue date.',
			hidden: false,
			templates: ['isp_welcome', 'isp_welcome_dd', 'isp_invoice', 'isp_invoice_dd', 'isp_dd_fail', 'isp_dd_fail_card', 'isp_card_fail', 'isp_card_expires', 'isp_card_expired', 'isp_dd_cancelled']
		},
		{
			name: '{INVOICEDUE}',
			description: 'Invoice due date.',
			hidden: false,
			templates: ['isp_welcome', 'isp_welcome_dd', 'isp_invoice', 'isp_invoice_dd', 'isp_dd_fail', 'isp_dd_fail_card', 'isp_card_fail', 'isp_card_expires', 'isp_card_expired', 'isp_dd_cancelled']
		},
		{
			name: '{INVOICESTART}',
			description: 'Invoice period start date.',
			hidden: false,
			templates: ['isp_welcome', 'isp_welcome_dd', 'isp_invoice', 'isp_invoice_dd', 'isp_dd_fail', 'isp_dd_fail_card', 'isp_card_fail', 'isp_card_expires', 'isp_card_expired', 'isp_dd_cancelled']
		},
		{
			name: '{INVOICEEND}',
			description: 'Invoice period end date.',
			hidden: false,
			templates: ['isp_welcome', 'isp_welcome_dd', 'isp_invoice', 'isp_invoice_dd', 'isp_dd_fail', 'isp_dd_fail_card', 'isp_card_fail', 'isp_card_expires', 'isp_card_expired', 'isp_dd_cancelled']
		},
		{
			name: '{INVOICENO}',
			description: 'Invoice number.',
			hidden: false,
			templates: ['isp_welcome', 'isp_welcome_dd', 'isp_invoice', 'isp_invoice_dd', 'isp_dd_fail', 'isp_dd_fail_card', 'isp_card_fail', 'isp_card_expires', 'isp_card_expired', 'isp_dd_cancelled']
		},
		{
			name: '{1STDAYOFNEXTMONTH}',
			description: 'First day of next month.',
			hidden: false,
			templates: ['isp_not_signed', 'isp_welcome', 'isp_welcome_dd', 'isp_activate', 'isp_invoice', 'isp_invoice_dd', 'isp_dd_fail', 'isp_dd_fail_card', 'isp_card_fail', 'isp_card_expires', 'isp_card_expired', 'isp_dd_cancelled']
		},
		{
			name: '{CARDEXPMONTH}',
			description: 'Payment card\'s expiry month.',
			hidden: false,
			templates: ['isp_dd_fail_card', 'isp_card_fail', 'isp_card_expires', 'isp_card_expired']
		},
		{
			name: '{CARDEXPYEAR}',
			description: 'Payment card\'s expiry year.',
			hidden: false,
			templates: ['isp_dd_fail_card', 'isp_card_fail', 'isp_card_expires', 'isp_card_expired']
		},
		{
			name: '{CARDLAST4}',
			description: 'Payment card\'s last 4 digits.',
			hidden: false,
			templates: ['isp_dd_fail_card', 'isp_card_fail', 'isp_card_expires', 'isp_card_expired']
		},
		{
			name: '{CARDPAYMENTDATE}',
			description: 'Date of automatic card payment after Direct Debit failure.',
			hidden: false,
			templates: ['isp_dd_fail_card']
		},
		{
			name: '{SIGNUPLINK}',
			description: 'Clickable link to the customer\'s signup page.',
			hidden: false,
			templates: ['isp_not_signed', 'isp_welcome', 'isp_welcome_dd', 'isp_activate', 'custom_1', 'custom_2', 'custom_3', 'custom_4', 'custom_5', 'custom_6', 'custom_7', 'custom_8', 'custom_9', 'custom_10']
		},
		{
			name: '{SIGNUPURL}',
			description: 'URL for the above. Can be used in HTML code to customise the link description.',
			hidden: false,
			templates: ['isp_not_signed', 'isp_welcome', 'isp_welcome_dd', 'isp_activate', 'custom_1', 'custom_2', 'custom_3', 'custom_4', 'custom_5', 'custom_6', 'custom_7', 'custom_8', 'custom_9', 'custom_10']
		},
	];

	showAllTags = false;

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
			this.templateType = params['template'];

			// Hide ISP specific tags for non-SI accounts
			this.tags.forEach(item => {
				item.hidden = false;
				if (this.level !== 'SI' && ['{PACKAGE}', '{WIFISSID}', '{WIFIPASSWORD}', '{SIGNUPLINK}', '{SIGNUPURL}'].indexOf(item.name) !== -1) {
					item.hidden = true;
				}
			});

			this.api.settings.getEmailTemplate(this.level, this.levelId, this.templateType, response => {
				this.details = response.data.details;
			}, response => {
				this.app.notifications.showDanger(response.message);
			});
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
		this.api.settings.saveEmailTemplate(this.details, () => {
			this.disabled = false;
			this.goBack();
			this.app.notifications.showSuccess('Email template saved.');
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

	deleteTemplate() {
		if (confirm('Are you sure you want to delete this email template?')) {
			this.disabled = true;
			this.api.settings.deleteEmailTemplate(this.level, this.levelId, this.templateType, () => {
				this.disabled = false;
				this.goBack();
				this.app.notifications.showSuccess('Email template deleted.');
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
