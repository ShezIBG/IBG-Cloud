import { DecimalPipe } from './../../shared/decimal.pipe';
import { StockProductLabourCategoryModalComponent } from './../stock-product-labour-category-modal/stock-product-labour-category-modal.component';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, NgModuleRef, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-stock-product-labour',
	templateUrl: './stock-product-labour.component.html'
})
export class StockProductLabourComponent implements OnInit, OnDestroy {

	types: any[] = [];
	categories: any[];
	pricing = false;

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
		this.api.products.listLabourTypes(this.app.selectedProductOwner, response => {
			this.types = response.data.types || [];
			this.categories = response.data.categories || [];
			this.pricing = response.data.pricing;
			this.app.resolveProductOwners(response);

			// Prepare categories
			const categoryIndex = {};
			this.categories.forEach(item => {
				item.items = [];
				categoryIndex[item.id] = item;
			});

			// Unassigned category
			const unassigned = {
				id: 0,
				description: 'Unassigned',
				items: []
			}
			categoryIndex[0] = unassigned;

			this.types.forEach(item => {
				const c = item.hourly_cost || 0;
				const p = item.hourly_price || 0;
				const pr = p - c;
				item.markup = DecimalPipe.transform(c === 0 ? 0 : pr / c * 100, 2) + '%';
				item.margin = DecimalPipe.transform(p === 0 ? 0 : pr / p * 100, 2) + '%';

				// Add to category
				const category = categoryIndex[item.category_id || 0];
				if (category) category.items.push(item);
			});

			// If there are unassigned categories, add them to the category list
			if (unassigned.items.length) this.categories.unshift(unassigned);

		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	editCategory(id = 'new') {
		const modalSub = this.app.modal.modalService.modalClosed.subscribe(() => {
			modalSub.unsubscribe();
			this.refresh();
		});

		this.app.modal.open(StockProductLabourCategoryModalComponent, this.moduleRef, {
			id, owner: this.app.getProductOwnerRecord()
		});
	}

}
