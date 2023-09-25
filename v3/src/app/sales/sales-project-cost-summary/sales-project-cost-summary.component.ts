import { DecimalPipe } from './../../shared/decimal.pipe';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnDestroy } from '@angular/core';

declare var window: any;

@Component({
	selector: 'app-sales-project-cost-summary',
	templateUrl: './sales-project-cost-summary.component.html',
	styleUrls: ['./sales-project-cost-summary.component.less']
})
export class SalesProjectCostSummaryComponent implements OnDestroy {

	projectId;
	data;

	private sub: any;

	constructor(
		private app: AppService,
		private api: ApiService,
		private route: ActivatedRoute
	) {
		this.sub = this.route.params.subscribe(params => {
			this.projectId = params['projectId'];
			this.loadSummary();
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

	loadSummary() {
		this.data = null;

		this.api.sales.getProjectCostSummary(this.projectId, response => {
			this.data = response.data;
			setTimeout(() => {
				this.app.header.setTab('cost-summary');
				this.app.header.addButton({
					icon: 'md md-print',
					text: 'Print',
					callback: () => window.print()
				});
			}, 0);
		}, response => {
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

}
