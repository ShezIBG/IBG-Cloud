import { DecimalPipe } from './../../shared/decimal.pipe';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnDestroy } from '@angular/core';

declare var Mangler: any;

@Component({
	selector: 'app-sales-project-price-adjustments',
	templateUrl: './sales-project-price-adjustments.component.html'
})
export class SalesProjectPriceAdjustmentsComponent implements OnDestroy {

	projectId;
	data;
	systems = [];
	total = 0;
	editable = false;
	disabled = false;

	private sub: any;

	constructor(
		private app: AppService,
		private api: ApiService,
		private route: ActivatedRoute
	) {
		this.sub = this.route.params.subscribe(params => {
			this.projectId = params['projectId'];
			this.loadAdjustments();
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

	loadAdjustments() {
		this.data = null;
		this.editable = false;
		this.total = 0;
		this.systems = [];

		this.api.sales.getProjectPriceAdjustments(this.projectId, response => {
			this.data = response.data.list;
			this.editable = response.data.editable;

			// Process items
			const systemIndex = {};
			Mangler.each(this.data, (k, v) => {
				this.format(v);

				// Group in systems
				let system = systemIndex[v.system_id];
				if (system) {
					system.items.push(v);
				} else {
					system = {
						id: v.system_id,
						description: v.system_description,
						items: [v]
					};

					this.systems.push(system);
					systemIndex[system.id] = system;
				}

				// Calculate margins
				v.margin = this.margin(v.base_unit_price, v.unit_cost);

				this.recalculate(v);
			});

			setTimeout(() => {
				this.app.header.setTab('price-adjustments');
			}, 0);
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	save() {
		this.disabled = true;

		this.api.sales.saveProjectPriceAdjustments(this.projectId, this.data, () => {
			this.disabled = false;
			this.app.notifications.showSuccess('Price adjustments updated.');
			this.loadAdjustments();
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

	margin(price, cost) {
		price = DecimalPipe.parse(price);
		cost = DecimalPipe.parse(cost);
		const profit = price - cost;
		return price === 0 ? 0 : profit / price * 100;
	}

	markup(price, cost) {
		price = DecimalPipe.parse(price);
		cost = DecimalPipe.parse(cost);
		const profit = price - cost;
		return cost === 0 ? 0 : profit / cost * 100;
	}

	profit(price, cost) {
		price = DecimalPipe.parse(price);
		cost = DecimalPipe.parse(cost);
		return price - cost;
	}

	recalculate(item) {
		const cost = DecimalPipe.parse(item.unit_cost);
		const amount = DecimalPipe.parse(item.amount);
		let price = DecimalPipe.parse(item.base_unit_price);

		switch (item.type) {
			case 'fixed_price':
				price = amount;
				break;

			case 'fixed_markup':
				price = cost * (1 + amount / 100);
				break;

			case 'fixed_margin':
				if (amount >= 100) {
					price = 0;
				} else {
					price = cost / (1 - (amount / 100));
				}
				break;

			case 'fixed_profit':
				price = cost + amount;
				break;

			case 'adjustment_percentage':
				price *= 1 + (amount / 100);
				break;

			case 'adjustment_pounds':
				price += amount;
				break;
		}

		item.unit_price = price;
	}

	format(item) {
		item.amount = DecimalPipe.transform(item.amount, 2, 4, false);
	}

}
