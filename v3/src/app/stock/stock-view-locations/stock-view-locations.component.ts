import { Pagination } from 'app/shared/pagination';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-stock-view-locations',
	templateUrl: './stock-view-locations.component.html',
	styleUrls: ['./stock-view-locations.component.less']
})
export class StockViewLocationsComponent implements OnInit, OnDestroy {

	warehouseList: any[];
	warehouse = null;
	list = null;
	pagination = new Pagination();
	search = '';
	showCost = false;
	totalStockCost = 0;

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
		this.api.stock.listWarehouses(this.app.selectedProductOwner, response => {
			this.warehouseList = response.data.list || [];
			this.app.resolveProductOwners(response);

			this.warehouse = this.warehouseList[0] ? this.warehouseList[0].id : null;
			this.refreshList();

			this.app.header.clearAll();
			this.app.header.addCrumb({ description: 'Stock By Location' });
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	refreshList() {
		this.list = null;

		if (!this.warehouse) {
			this.showCost = false;
			this.totalStockCost = 0;
			return;
		}

		this.api.stock.listStockByLocation(this.warehouse, response => {
			this.list = response.data.list;
			this.showCost = response.data.show_cost;
			this.totalStockCost = response.data.total_stock_cost;
		});
	}

	stockTooFew(item) {
		return item.min_qty && item.qty < item.min_qty;
	}

	stockTooMany(item) {
		return item.max_qty && item.qty > item.max_qty;
	}

}
