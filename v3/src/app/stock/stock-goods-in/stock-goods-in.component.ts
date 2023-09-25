import { StockProductSelectModalComponent } from './../stock-product-select-modal/stock-product-select-modal.component';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, NgModuleRef, ViewChild, OnDestroy } from '@angular/core';

declare var Mangler: any;

@Component({
	selector: 'app-stock-goods-in',
	templateUrl: './stock-goods-in.component.html',
	styleUrls: ['./stock-goods-in.component.less']
})
export class StockGoodsInComponent implements OnInit, OnDestroy {

	@ViewChild('searchfield') searchfield;

	warehouseList: any[];
	warehouse = null;
	sku = '';
	notes = '';
	products = [];
	locations = [];
	locationIndex = {};

	private sub: any;

	constructor(
		public app: AppService,
		private api: ApiService,
		private moduleRef: NgModuleRef<any>
	) { }

	ngOnInit() {
		this.sub = this.app.productOwnerChanged.subscribe(() => this.refresh());
		this.refresh();
	}

	ngOnDestroy() {
		this.app.blockOwnerChange = false;
		this.sub.unsubscribe();
	}

	refresh() {
		this.api.stock.listWarehouses(this.app.selectedProductOwner, response => {
			this.warehouseList = response.data.list || [];
			this.app.resolveProductOwners(response);

			this.warehouse = this.warehouseList[0] ? this.warehouseList[0].id : null;
			this.refreshLocations();

			this.app.header.clearAll();
			this.app.header.addCrumb({ description: 'Goods In' });
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	refreshLocations() {
		this.locations = [];
		this.locationIndex = {};

		if (this.warehouse) {
			this.api.stock.listStockLocations(this.warehouse, response => {
				this.locations = response.data.list;
				this.locationIndex = Mangler.index(this.locations, 'id');
			});
		}
	}

	search(alwaysClear = false) {
		if (!this.sku || !this.locations.length) return;

		const sku = this.sku;
		if (alwaysClear) this.sku = '';

		this.api.products.listProducts({
			product_owner: this.app.selectedProductOwner,
			is_stocked: 1,
			sku_match: sku
		}, response => {
			if (response.data.list.length === 0) {
				// Not found
				this.app.notifications.showDanger('Product not found.');
			} else if (response.data.list.length > 1) {
				// Multiple products found, popup select modal

				if (this.searchfield.nativeElement.blur) this.searchfield.nativeElement.blur(); // Fix change detection error

				this.app.modal.open(StockProductSelectModalComponent, this.moduleRef, {
					product_owner: this.app.selectedProductOwner,
					is_stocked: 1,
					sku_match: sku
				});

				const modalSub = this.app.modal.modalService.modalClosed.subscribe(event => {
					modalSub.unsubscribe();
					if (this.searchfield.nativeElement.focus) this.searchfield.nativeElement.focus();

					if (event.data) {
						this.addProduct(event.data);
						this.sku = '';
					}
				});
			} else {
				// Only one product has been found, add
				this.addProduct(response.data.list[0]);
			}
		});
	}

	addProduct(product) {
		if (!product || !this.locations.length) return;

		if (this.searchfield.nativeElement.focus) this.searchfield.nativeElement.focus();

		this.api.stock.getStockProductInfo(product.id, this.warehouse, response => {
			const p = response.data;

			p.default_location_id = p.location_id;
			p.quantity = 1;
			delete p.stock;

			this.products.unshift(p);
			this.products = this.products.slice();
			this.app.blockOwnerChange = !!this.products.length;
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	deleteProduct(product) {
		const i = this.products.indexOf(product);
		if (i !== -1) {
			this.products.splice(i, 1);
			this.products = this.products.slice();
			this.app.blockOwnerChange = !!this.products.length;
		}
	}

	selectProduct() {
		this.app.modal.open(StockProductSelectModalComponent, this.moduleRef, {
			product_owner: this.app.selectedProductOwner,
			is_stocked: 1
		});

		const modalSub = this.app.modal.modalService.modalClosed.subscribe(event => {
			modalSub.unsubscribe();
			if (this.searchfield.nativeElement.focus) this.searchfield.nativeElement.focus();

			if (event.data) {
				this.addProduct(event.data);
			}
		});
	}

	resetForm() {
		this.notes = '';
		this.products = [];
		this.app.blockOwnerChange = false;
		if (this.searchfield.nativeElement.focus) this.searchfield.nativeElement.focus();
	}

	cancel() {
		if (confirm('Are you sure you want to CANCEL?')) {
			this.resetForm();
		}
	}

	submit() {
		const items = [];

		this.products.forEach(item => {
			items.push({
				product_id: item.id,
				location_id: item.location_id,
				quantity: item.quantity
			});
		});

		this.api.stock.submitGoodsIn({
			warehouse_id: this.warehouse,
			notes: this.notes,
			items: items
		}, () => {
			this.app.notifications.showSuccess('Goods in submitted.');
			this.resetForm();
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

}
