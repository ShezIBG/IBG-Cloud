import { Location } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-stock-warehouse-edit',
	templateUrl: './stock-warehouse-edit.component.html'
})
export class StockWarehouseEditComponent implements OnInit, OnDestroy {

	id;
	details;
	locationCount = 0;
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

			const success = response => {
				this.details = response.data.details || {};
				this.locationCount = response.data.location_count || 0;

				this.app.header.clearAll();
				this.app.header.addCrumbs([
					{ description: 'Warehouses', route: '/stock/warehouse' },
					{ description: this.id === 'new' ? 'New Warehouse' : this.details.description }
				]);
			}

			const fail = response => {
				this.app.notifications.showDanger(response.message);
			};

			if (this.id === 'new') {
				this.api.stock.newWarehouse(owner, success, fail);
			} else {
				this.api.stock.getWarehouse(this.id, success, fail);
			}
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

	goBack() {
		this.location.back();
	}

	save() {
		this.disabled = true;
		this.api.stock.saveWarehouse(this.details, () => {
			this.disabled = false;
			this.goBack();
			if (this.details.id === 'new') {
				this.app.notifications.showSuccess('Warehouse created.');
			} else {
				this.app.notifications.showSuccess('Warehouse updated.');
			}
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

	delete() {
		if (this.id === 'new') return;

		this.disabled = true;
		this.api.stock.deleteWarehouse(this.id, () => {
			this.disabled = false;
			this.goBack();
			this.app.notifications.showSuccess('Warehouse deleted.');
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

}
