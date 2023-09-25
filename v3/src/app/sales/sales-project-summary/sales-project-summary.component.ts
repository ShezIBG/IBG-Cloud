import { DecimalPipe } from './../../shared/decimal.pipe';
import { AppService } from './../../app.service';
import { ApiService } from './../../api.service';
import { ActivatedRoute } from '@angular/router';
import { Component, OnDestroy } from '@angular/core';

declare var window: any;

@Component({
	selector: 'app-sales-project-summary',
	templateUrl: './sales-project-summary.component.html',
	styleUrls: ['./sales-project-summary.component.less']
})
export class SalesProjectSummaryComponent implements OnDestroy {

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

		this.api.sales.getProjectSummary(this.projectId, response => {
			this.data = response.data;
			this.app.header.setTab('summary');
			this.app.header.addButton({
				icon: 'md md-print',
				text: 'Print',
				callback: () => window.print()
			});
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	getProjectTotal() {
		return (this.data.equipment.total_price || 0) + (this.data.labour.total_price || 0);
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

}
