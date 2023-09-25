import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';
import { StockService } from '../stock.service';
import { Pagination } from 'app/shared/pagination';

@Component({
	selector: 'app-stock-products',
	templateUrl: './stock-products.component.html',
	styleUrls: ['./stock-products.component.less']
})
export class StockProductsComponent implements OnInit, OnDestroy {

	list: any[];
	duplicateSKU: any[];
	pagination = new Pagination();

	private sub: any;

	constructor(
		public app: AppService,
		private api: ApiService,
		public stock: StockService
	) { }

	ngOnInit() {
		this.sub = this.app.productOwnerChanged.subscribe(() => this.refresh());
		this.refresh();
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

	refresh() {
		this.api.products.listProducts({
			product_owner: this.app.selectedProductOwner,
			order: this.stock.productOrder,
			discontinued: 1
		}, response => {
			this.list = response.data.list || [];
			this.duplicateSKU = response.data.duplicate_sku;
			this.app.resolveProductOwners(response);

			const tagIndex: any = {};
			(response.data.tags || []).forEach(tag => tagIndex[tag.id] = tag);

			this.list.forEach(product => {
				const tags = [];
				(product.tags || '').split(' ').forEach(id => {
					if (tagIndex[id]) tags.push(tagIndex[id]);
				});
				product.tags = tags;
			});

		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	orderColumn(column) {
		this.stock.productOrder = (this.stock.productOrder === column.field ? '-' : '') + column.field;
		this.refresh();
	}

	isDuplicateSKU(sku) {
		return sku ? this.duplicateSKU.indexOf(sku) !== -1 : false;
	}

}
