import { DecimalPipe } from './../../shared/decimal.pipe';
import { Location } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

declare var Mangler: any;

@Component({
	selector: 'app-stock-product-pricing-edit',
	templateUrl: './stock-product-pricing-edit.component.html'
})
export class StockProductPricingEditComponent implements OnInit, OnDestroy {

	id;
	details;
	linkCount = 0;
	disabled = false;

	sample: any = {
		cost: 100.00,
		distribution_price: 0,
		distribution_message: '',
		reseller_price: 0,
		reseller_message: '',
		trade_price: 0,
		trade_message: '',
		retail_price: 0,
		retail_message: ''
	};

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
				this.linkCount = (response.data.product_count || 0) + (response.data.subscription_count || 0);
				this.formatNumbers();

				this.app.header.clearAll();
				this.app.header.addCrumbs([
					{ description: 'Product Catalogue Configuration', route: '/stock/product-config' },
					{ description: 'Pricing structures', route: '/stock/product-config/pricing' },
					{ description: this.id === 'new' ? 'New Pricing Structure' : this.details.description }
				]);
			}

			const fail = response => {
				this.app.notifications.showDanger(response.message);
			};

			if (this.id === 'new') {
				this.api.products.newPricingStructure(owner, success, fail);
			} else {
				this.api.products.getPricingStructure(this.id, success, fail);
			}
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

	formatNumbers(addThousandSeparators: boolean = true) {
		['distribution', 'reseller', 'trade', 'retail'].forEach(tier => {
			this.details[tier + '_value'] = DecimalPipe.transform(this.details[tier + '_value'], 2, 4, addThousandSeparators);
			this.details[tier + '_round_to_nearest'] = DecimalPipe.transform(this.details[tier + '_round_to_nearest'], 2, 2, addThousandSeparators);
			this.details[tier + '_minimum_price'] = DecimalPipe.transform(this.details[tier + '_minimum_price'], 2, 4, addThousandSeparators);

			if (this.details[tier + '_method'] === 'custom') this.details[tier + '_round'] = null;
		});
		this.sample.cost = DecimalPipe.transform(this.sample.cost, 2, 4, addThousandSeparators);

		this.refreshSample(addThousandSeparators);
	}

	refreshSample(addThousandSeparators: boolean = true) {
		const cost = DecimalPipe.parse(this.sample.cost);

		['distribution', 'reseller', 'trade', 'retail'].forEach(tier => {
			const method = this.details[tier + '_method'];
			const value = DecimalPipe.parse(this.details[tier + '_value']);
			const round = this.details[tier + '_round'];
			const nearest = DecimalPipe.parse(this.details[tier + '_round_to_nearest']);
			const minimum = DecimalPipe.parse(this.details[tier + '_minimum_price']);

			let price = 0;
			let message = '';

			switch (method) {
				case 'custom':
					message = 'Custom';
					break;

				case 'recommended':
					message = 'Recommended';
					break;

				case 'markup':
					price = cost * (1 + value / 100);
					break;

				case 'margin':
					if (value >= 100) {
						message = 'Invalid margin';
					} else {
						price = cost / (1 - (value / 100));
					}
					break;

				case 'profit':
					price = cost + value;
					break;
			}

			if (round) {
				if (nearest > 0) {
					price /= nearest;
					switch (round) {
						case 'round': price = Math.round(price); break;
						case 'floor': price = Math.floor(price); break;
						case 'ceiling': price = Math.ceil(price); break;
					}
					price *= nearest;
				} else {
					message = 'Invalid rounding value';
				}
			}

			if (method !== 'custom' && method !== 'recommended') price = Math.max(price, minimum);

			this.sample[tier + '_price'] = DecimalPipe.transform(price, 2, 4, addThousandSeparators);
			this.sample[tier + '_message'] = message;
		});
	}

	goBack() {
		this.location.back();
	}

	save() {
		this.formatNumbers(false);
		const pricingDetails = Mangler.clone(this.details);
		this.formatNumbers();

		this.disabled = true;
		this.api.products.savePricingStructure(pricingDetails, () => {
			this.disabled = false;
			this.goBack();
			if (this.details.id === 'new') {
				this.app.notifications.showSuccess('Pricing structure created.');
			} else {
				this.app.notifications.showSuccess('Pricing structure updated.');
			}
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

	delete() {
		if (this.id === 'new') return;

		this.disabled = true;
		this.api.products.deletePricingStructure(this.id, () => {
			this.disabled = false;
			this.goBack();
			this.app.notifications.showSuccess('Pricing structure deleted.');
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

}
