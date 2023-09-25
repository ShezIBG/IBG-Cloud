import { StockService } from './../stock.service';
import { Pagination } from 'app/shared/pagination';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-stock-product-entities',
	templateUrl: './stock-product-entities.component.html'
})
export class StockProductEntitiesComponent implements OnInit, OnDestroy {

	list: any;
	search = '';
	pagination = new Pagination();

	ownerHasEntity = false;

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
		this.api.products.listEntities(this.app.selectedProductOwner, {
			archived: this.stock.showArchivedEntities
		}, response => {
			this.list = response.data.list || [];
			this.ownerHasEntity = response.data.owner_has_entity;
			this.app.resolveProductOwners(response);
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

}
