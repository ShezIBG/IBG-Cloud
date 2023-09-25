import { DecimalPipe } from './../../shared/decimal.pipe';
import { Location } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

declare var Mangler: any;

@Component({
	selector: 'app-stock-product-subscription-edit',
	templateUrl: './stock-product-subscription-edit.component.html'
})
export class StockProductSubscriptionEditComponent implements OnInit, OnDestroy {

	id;
	owner;
	details;
	editable;
	categories;
	pricingStructures;
	recommendedPricing;
	disabled = false;

	selectedPricingStructure = null;
	defaultPricingStructure = {
		distribution_method: 'custom',
		reseller_method: 'custom',
		trade_method: 'custom',
		retail_method: 'custom'
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
			const categoryId = parseInt(params['categoryId'], 10) || 0;
			this.owner = params['owner'];
			this.id = params['id'] || 'new';
			this.details = null;

			const success = response => {
				this.details = response.data.details || {};
				this.editable = response.data.editable;
				this.categories = response.data.categories;
				this.pricingStructures = response.data.pricing_structures;
				this.recommendedPricing = response.data.recommended_pricing;

				if (this.details.id === 'new' && categoryId) this.details.category_id = categoryId;
				if (!this.details.category_id) this.details.category_id = this.categories[0] ? this.categories[0].id : null;

				this.refreshSelections();
				this.formatNumbers();

				this.app.header.clearAll();
				this.app.header.addCrumbs([
					{ description: 'Product Catalogue Configuration', route: '/stock/product-config' },
					{ description: 'Subscription types', route: '/stock/product-config/subscription' },
					{ description: this.id === 'new' ? 'New Subscription Type' : this.details.description }
				]);
			}

			const fail = response => {
				this.app.notifications.showDanger(response.message);
			};

			if (this.id === 'new') {
				this.api.products.newSubscriptionType(this.owner, success, fail);
			} else {
				this.api.products.getSubscriptionType(this.id, this.owner, success, fail);
			}
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

	refreshSelections() {
		this.selectedPricingStructure = this.details.pricing_structure_id ? Mangler.first(this.pricingStructures, { id: this.details.pricing_structure_id }) || this.defaultPricingStructure : this.defaultPricingStructure;
		this.recalculatePricing();
	}

	recalculatePricing(dontReformat = '', addThousandSeparators: boolean = true) {
		// Calculate product cost
		const cost = DecimalPipe.parse(this.details.unit_cost);

		// Calculate prices
		['distribution', 'reseller', 'trade', 'retail'].forEach(tier => {
			const method = this.selectedPricingStructure[tier + '_method'];
			const value = DecimalPipe.parse(this.selectedPricingStructure[tier + '_value']);
			const round = this.selectedPricingStructure[tier + '_round'];
			const nearest = DecimalPipe.parse(this.selectedPricingStructure[tier + '_round_to_nearest']);
			const minimum = DecimalPipe.parse(this.selectedPricingStructure[tier + '_minimum_price']);

			let price = 0;

			switch (method) {
				case 'custom':
					// Leave it as-is
					return;

				case 'recommended':
					price = this.recommendedPricing[tier + '_price'];
					break;

				case 'markup':
					price = cost * (1 + value / 100);
					break;

				case 'margin':
					if (value >= 100) {
						price = 0;
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
				}
			}

			if (method !== 'custom' && method !== 'recommended') price = Math.max(price, minimum);

			this.details[tier + '_price'] = DecimalPipe.transform(price, 2, 4, addThousandSeparators);
		});

		if (dontReformat !== 'cost') this.details.unit_cost = DecimalPipe.transform(cost, 2, 4, addThousandSeparators);
	}

	formatNumbers(addThousandSeparators: boolean = true) {
		this.details.unit_cost = DecimalPipe.transform(this.details.unit_cost, 2, 4, addThousandSeparators);
		this.details.distribution_price = DecimalPipe.transform(this.details.distribution_price, 2, 4, addThousandSeparators);
		this.details.reseller_price = DecimalPipe.transform(this.details.reseller_price, 2, 4, addThousandSeparators);
		this.details.trade_price = DecimalPipe.transform(this.details.trade_price, 2, 4, addThousandSeparators);
		this.details.retail_price = DecimalPipe.transform(this.details.retail_price, 2, 4, addThousandSeparators);
	}

	goBack() {
		this.location.back();
	}

	save() {
		this.formatNumbers(false);
		const subscriptionDetails = Mangler.clone(this.details);
		this.formatNumbers();

		this.disabled = true;
		this.api.products.saveSubscriptionType(this.owner, subscriptionDetails, () => {
			this.disabled = false;
			this.goBack();
			if (this.details.id === 'new') {
				this.app.notifications.showSuccess('Subscription type created.');
			} else {
				this.app.notifications.showSuccess('Subscription type updated.');
			}
		}, response => {
			this.disabled = false;
			this.app.notifications.showDanger(response.message);
		});
	}

}
