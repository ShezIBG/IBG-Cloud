import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-stock-product-units',
	templateUrl: './stock-product-units.component.html',
	styleUrls: ['./stock-product-units.component.less']
})
export class StockProductUnitsComponent implements OnInit, OnDestroy {

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
		this.api.products.listBaseUnits(this.app.selectedProductOwner, response => {
			this.list = response.data.list || [];
			this.app.resolveProductOwners(response);

			const index = {};
			this.list.forEach(baseUnit => {
				index[baseUnit.id] = baseUnit;
				baseUnit.units = [];
				baseUnit.unit_names = '';
				baseUnit.is_default = !!baseUnit.is_default;
			});
			response.data.units.forEach(unit => {
				const baseUnit = index[unit.base_unit_id];
				if (baseUnit) {
					baseUnit.units.push(unit);
					baseUnit.unit_names += unit.name + ' ';
				}
			});
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	setDefaultUnit(unitId) {
		this.api.products.setDefaultUnit(this.app.selectedProductOwner, unitId, () => {
			this.app.notifications.showSuccess('Default unit has been updated.');
			this.refresh();
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

}
