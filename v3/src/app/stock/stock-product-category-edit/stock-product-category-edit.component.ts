import { Location } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-stock-product-category-edit',
	templateUrl: './stock-product-category-edit.component.html'
})
export class StockProductCategoryEditComponent implements OnInit, OnDestroy {

	id;
	details;
	productCount = 0;
	disabled = false;

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

			this.details = null;

			const success = response => {
				this.details = response.data.details || {};
				this.productCount = response.data.product_count || 0;

				this.app.header.clearAll();
				this.app.header.addCrumbs([
					{ description: 'Product Catalogue Configuration', route: '/stock/product-config' },
					{ description: 'Categories', route: '/stock/product-config/category' },
					{ description: this.id === 'new' ? 'New Category' : this.details.name }
				]);
			}

			const fail = response => {
				this.app.notifications.showDanger(response.message);
			};

			if (this.id === 'new') {
				this.api.products.newCategory(owner, success, fail);
			} else {
				this.api.products.getCategory(this.id, success, fail);
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
		this.api.products.saveCategory(this.details, () => {
			this.disabled = false;
			this.goBack();
			if (this.details.id === 'new') {
				this.app.notifications.showSuccess('Category created.');
			} else {
				this.app.notifications.showSuccess('Category updated.');
			}
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

	delete() {
		if (this.id === 'new') return;

		this.disabled = true;
		this.api.products.deleteCategory(this.id, () => {
			this.disabled = false;
			this.goBack();
			this.app.notifications.showSuccess('Category deleted.');
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

}
