import { Location } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-stock-product-unit-edit',
	templateUrl: './stock-product-unit-edit.component.html'
})
export class StockProductUnitEditComponent implements OnInit, OnDestroy {

	id;
	details;
	units = [];
	deletedUnits = [];
	highlightedUnit = null;
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
			this.units = null;
			this.deletedUnits = [];

			const success = response => {

				this.details = response.data.details || {};
				this.units = response.data.units || [];

				// Set modified/deleted flags
				this.units.forEach(unit => {
					unit.modified = false;
				});

				this.app.header.clearAll();
				this.app.header.addCrumbs([
					{ description: 'Product Catalogue Configuration', route: '/stock/product-config' },
					{ description: 'Units', route: '/stock/product-config/unit' },
					{ description: this.id === 'new' ? 'New Base Unit' : this.details.name }
				]);
			}

			const fail = response => {
				this.app.notifications.showDanger(response.message);
			};

			if (this.id === 'new') {
				this.api.products.newBaseUnit(owner, success, fail);
			} else {
				this.api.products.getBaseUnit(this.id, success, fail);
			}
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

	addUnit() {
		const unit = {
			id: 'new',
			name: '',
			description: '',
			decimal_places: this.details.decimal_places,
			base_amount: 1,
			modified: false
		};

		this.units.push(unit);
		this.units = this.units.slice();
		this.highlightedUnit = unit;
	}

	deleteUnit(unit) {
		const i = this.units.indexOf(unit);
		if (i !== -1) {
			this.units.splice(i, 1);
			this.units = this.units.slice();

			if (unit.id !== 'new') {
				this.deletedUnits.push(unit);
				this.deletedUnits = this.deletedUnits.slice();
				this.highlightedUnit = unit;
			}
		}
	}

	undeleteUnit(unit) {
		const i = this.deletedUnits.indexOf(unit);
		if (i !== -1) {
			this.deletedUnits.splice(i, 1);
			this.deletedUnits = this.deletedUnits.slice();

			this.units.push(unit);
			this.units = this.units.slice();

			this.highlightedUnit = unit;
		}
	}

	goBack() {
		this.location.back();
	}

	save() {
		const data = {
			details: this.details,
			deleted: [],
			modified: [],
			added: []
		};

		this.units.forEach(unit => {
			if (unit.id === 'new') {
				data.added.push(unit);
			} else if (unit.modified) {
				data.modified.push(unit);
			}
		});

		this.deletedUnits.forEach(unit => {
			data.deleted.push(unit);
		});

		this.disabled = true;
		this.api.products.saveBaseUnit(data, () => {
			this.disabled = false;
			this.goBack();
			if (this.details.id === 'new') {
				this.app.notifications.showSuccess('Base unit created.');
			} else {
				this.app.notifications.showSuccess('Base unit updated.');
			}
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

}
