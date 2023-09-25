import { DecimalPipe } from './../../shared/decimal.pipe';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-stock-product-pricing',
	templateUrl: './stock-product-pricing.component.html'
})
export class StockProductPricingComponent implements OnInit, OnDestroy {

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
		this.api.products.listPricingStructures(this.app.selectedProductOwner, response => {
			this.list = response.data.list || [];
			this.app.resolveProductOwners(response);

			this.list.forEach(item => {
				['distribution', 'reseller', 'trade', 'retail'].forEach(tier => {
					const method = item[tier + '_method'];
					const value = DecimalPipe.transform(item[tier + '_value'], 2);
					const round = item[tier + '_round'];
					const nearest = item[tier + '_round_to_nearest'];
					const minimum = DecimalPipe.transform(item[tier + '_minimum_price'], 2);

					const info = [];

					switch (method) {
						case 'custom':
							info.push('<b>Custom</b>');
							break;

						case 'recommended':
							info.push('<b>Recommended</b>');
							break;

						case 'markup':
							info.push('<b>Markup</b>: ' + value + '%');
							break;

						case 'margin':
							info.push('<b>Margin</b>: ' + value + '%');
							break;

						case 'profit':
							info.push('<b>Profit</b>: &pound;' + value);
							break;
					}

					if (method !== 'custom') {
						if (round) {
							switch (round) {
								case 'round': info.push('<span class="subtitle">rounded to nearest &pound;' + nearest + '</span>'); break;
								case 'floor': info.push('<span class="subtitle">rounded down to nearest &pound;' + nearest + '</span>'); break;
								case 'ceiling': info.push('<span class="subtitle">rounded up to nearest &pound;' + nearest + '</span>'); break;
							}
						}

						if (minimum !== '0.00') info.push('<span class="subtitle">minimum &pound;' + minimum + '</span>');
					}

					item[tier + '_info'] = info;
				});
			});
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

}
