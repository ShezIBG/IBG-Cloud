import { Injectable } from '@angular/core';
import { HeaderComponent } from './shared/header/header.component';
import { NotificationsComponent } from './shared/notifications/notifications.component';
import { ModalLoaderComponent } from './shared/modal/modal-loader.component';
import { SidebarComponent } from './shared/sidebar/sidebar.component';
import { EventEmitter } from '@angular/core';

declare var branding: string;
declare var window: any;

@Injectable()
export class AppService {

	modal: ModalLoaderComponent = null;
	notifications: NotificationsComponent = null;
	sidebar: SidebarComponent = null;
	header: HeaderComponent = null;

	private _routeData: any = {};
	get routeData() { return this._routeData; }
	set routeData(value) {
		this._routeData = value;
	}

	tinymce: any = null;

	productOwners = [];
	productOwnerChanged: EventEmitter<string> = new EventEmitter<string>();
	blockOwnerChange = false;

	branding = branding;

	private _productOwner = null;
	get selectedProductOwner() { return this._productOwner; }
	set selectedProductOwner(value) {
		if (value !== this._productOwner) {
			this._productOwner = value;
			this.productOwnerChanged.emit(this._productOwner);
		}
	}

	constructor() {
		const base = this.getBaseURL();

		this.tinymce = {
			tinymceScriptURL: base + 'assets/tinymce/tinymce.min.js',
			baseURL: base + 'assets/tinymce',
			theme_url: base + 'assets/tinymce/themes/modern/theme.min.js',
			branding: false,
			height: 300,
			statusbar: true,
			relative_urls: false,
			remove_script_host: false,
			convert_urls: false,
			image_advtab: true,
			paste_webkit_styles: 'color font-size',
			paste_retain_style_properties: 'color font-size',
			content_css: base + 'assets/css/editor-content.css',
			plugins: 'print preview searchreplace autolink directionality visualblocks visualchars image link media table charmap hr nonbreaking insertdatetime advlist lists textcolor wordcount imagetools contextmenu colorpicker textpattern code',
			toolbar1: 'formatselect | bold italic strikethrough forecolor backcolor | link | alignleft aligncenter alignright alignjustify  | numlist bullist outdent indent  | removeformat | code'
		};
	}

	getBaseURL() {
		const base = location.pathname.split('/');
		while (base.length && base[base.length - 1] !== 'v3') base.pop();
		return base.join('/') + '/';
	}

	getAppURL() {
		const base = location.pathname.split('/');
		while (base.length && base[base.length - 1] !== 'v3') base.pop();
		base.pop();
		return base.join('/') + '/dashboard';
	}

	redirect(url) {
		window.top.location.href = url;
	}

	/**
	 * Resolves productOwners list and selectedProductOwner from the returned data
	 *
	 * @param response The full response received from the API call
	 */
	resolveProductOwners(response) {
		this.productOwners = response.data.product_owners;
		this.selectedProductOwner = response.data.selected_product_owner;
	}

	/**
	 * Return the full selected owner record (or null if not found)
	 */
	getProductOwnerRecord() {
		if (!this.productOwners) return null;

		let result = null;
		this.productOwners.forEach(o => {
			if (this.selectedProductOwner === o.id) result = o;
		});

		return result;
	}

}
