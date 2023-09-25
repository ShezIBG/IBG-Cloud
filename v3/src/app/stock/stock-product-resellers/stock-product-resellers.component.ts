import { StockProductResellerModalComponent } from './../stock-product-reseller-modal/stock-product-reseller-modal.component';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, NgModuleRef, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-stock-product-resellers',
	templateUrl: './stock-product-resellers.component.html'
})
export class StockProductResellersComponent implements OnInit, OnDestroy {

	list: any;
	count = { list: 0 };
	search = '';

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
		this.sub.unsubscribe();
	}

	refresh() {
		this.api.products.listResellers(this.app.selectedProductOwner, response => {
			this.list = response.data.list || [];
			this.app.resolveProductOwners(response);
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	getPriceTier(tier) {
		switch (tier) {
			case 'cost': return 'Cost';
			case 'distribution': return 'Distribution price';
			case 'reseller': return 'Reseller price';
			case 'trade': return 'Trade price';
			case 'retail': return 'Retail price';
			default: return '';
		}
	}

	newReseller() {
		this.api.products.newReseller(this.app.selectedProductOwner, response => {
			const modalSub = this.app.modal.modalService.modalClosed.subscribe(() => {
				modalSub.unsubscribe();
				this.refresh();
			});

			this.app.modal.open(StockProductResellerModalComponent, this.moduleRef, response.data);
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	editReseller(item) {
		this.api.products.getReseller(item.owner, item.reseller, response => {
			const modalSub = this.app.modal.modalService.modalClosed.subscribe(() => {
				modalSub.unsubscribe();
				this.refresh();
			});

			this.app.modal.open(StockProductResellerModalComponent, this.moduleRef, response.data);
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

}
