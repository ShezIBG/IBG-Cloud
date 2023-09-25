import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-stock-product-categories',
	templateUrl: './stock-product-categories.component.html'
})
export class StockProductCategoriesComponent implements OnInit, OnDestroy {

	list: any;
	count = { list: 0 };
	search = '';

	private sub: any;

	constructor(
		public app: AppService,
		private api: ApiService
	) { }

	ngOnInit() {
		this.sub = this.app.productOwnerChanged.subscribe(() => this.refresh());
		this.refresh();
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

	refresh() {
		this.api.products.listCategories(this.app.selectedProductOwner, response => {
			this.list = response.data.list || [];
			this.app.resolveProductOwners(response);
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

}
