import { DecimalPipe } from './../../shared/decimal.pipe';
import { Location } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

declare var Mangler: any;

@Component({
	selector: 'app-stock-product-labour-edit',
	templateUrl: './stock-product-labour-edit.component.html'
})
export class StockProductLabourEditComponent implements OnInit, OnDestroy {

	id;
	details;
	categories;
	disabled = false;

	markup;
	margin;
	profit;

	private sub: any;

	constructor(
		private app: AppService,
		private api: ApiService,
		private route: ActivatedRoute,
		private location: Location
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			const categoryId = parseInt(params['categoryId'], 10) || 0;
			const owner = params['owner'];
			this.id = params['id'] || 'new';
			this.details = null;

			const success = response => {
				this.details = response.data.details || {};
				this.categories = response.data.categories;

				if (this.details.id === 'new' && categoryId) this.details.category_id = categoryId;
				if (!this.details.category_id) this.details.category_id = this.categories[0] ? this.categories[0].id : null;

				this.recalculate();

				this.app.header.clearAll();
				this.app.header.addCrumbs([
					{ description: 'Product Catalogue Configuration', route: '/stock/product-config' },
					{ description: 'Labour types', route: '/stock/product-config/labour' },
					{ description: this.id === 'new' ? 'New Labour Type' : this.details.description }
				]);
			}

			const fail = response => {
				this.app.notifications.showDanger(response.message);
			};

			if (this.id === 'new') {
				this.api.products.newLabourType(owner, success, fail);
			} else {
				this.api.products.getLabourType(this.id, success, fail);
			}
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

	recalculate(from = '', addThousandSeparators: boolean = true) {
		const c = DecimalPipe.parse(this.details.hourly_cost);
		let mu = DecimalPipe.parse(this.markup);
		let mg = DecimalPipe.parse(this.margin);
		let p = DecimalPipe.parse(this.details.hourly_price);
		let pr = DecimalPipe.parse(this.profit);

		switch (from) {
			case 'markup':
				pr = c * (mu / 100);
				p = c + pr;
				mg = p === 0 ? 0 : pr / p * 100;
				break;

			case 'margin':
				p = mg === 100 ? c : c / (1 - (mg / 100));
				pr = p - c;
				mu = c === 0 ? 0 : pr / c * 100;
				break;

			case 'profit':
				p = c + pr;
				mu = c === 0 ? 0 : pr / c * 100;
				mg = p === 0 ? 0 : pr / p * 100;
				break;

			case 'cost':
				pr = c * (mu / 100);
				p = c + pr;
				break;

			case 'price':
			default:
				pr = p - c;
				mu = c === 0 ? 0 : pr / c * 100;
				mg = p === 0 ? 0 : pr / p * 100;
				break;
		}

		if (from !== 'cost') this.details.hourly_cost = DecimalPipe.transform(c, 2, 2, addThousandSeparators);
		if (from !== 'markup') this.markup = DecimalPipe.transform(mu, 2, 2, addThousandSeparators);
		if (from !== 'margin') this.margin = DecimalPipe.transform(mg, 2, 2, addThousandSeparators);
		if (from !== 'price') this.details.hourly_price = DecimalPipe.transform(p, 2, 2, addThousandSeparators);
		if (from !== 'profit') this.profit = DecimalPipe.transform(pr, 2, 2, addThousandSeparators);
	}

	goBack() {
		this.location.back();
	}

	save() {
		this.recalculate('', false);
		const labourDetails = Mangler.clone(this.details);
		this.recalculate();

		this.disabled = true;
		this.api.products.saveLabourType(labourDetails, () => {
			this.disabled = false;
			this.goBack();
			if (this.details.id === 'new') {
				this.app.notifications.showSuccess('Labour type created.');
			} else {
				this.app.notifications.showSuccess('Labour type updated.');
			}
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

}
