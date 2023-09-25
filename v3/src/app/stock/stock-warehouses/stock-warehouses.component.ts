import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-stock-warehouses',
	templateUrl: './stock-warehouses.component.html'
})
export class StockWarehousesComponent implements OnInit, OnDestroy {

	list: any[];
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
		this.api.stock.listWarehouses(this.app.selectedProductOwner, response => {
			this.list = response.data.list || [];
			this.app.resolveProductOwners(response);

			this.app.header.clearAll();
			this.app.header.addCrumb({ description: 'Warehouses' });
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

}
