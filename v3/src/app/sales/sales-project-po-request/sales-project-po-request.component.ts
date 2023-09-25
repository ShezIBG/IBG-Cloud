import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnDestroy } from '@angular/core';

declare var Mangler: any;
declare var window: any;

@Component({
	selector: 'app-sales-project-po-request',
	templateUrl: './sales-project-po-request.component.html',
	styleUrls: ['./sales-project-po-request.component.less']
})
export class SalesProjectPORequestComponent implements OnDestroy {

	projectId;
	data;
	total = 0;

	private sub: any;

	constructor(
		private app: AppService,
		private api: ApiService,
		private route: ActivatedRoute
	) {
		this.sub = this.route.params.subscribe(params => {
			this.projectId = params['projectId'];
			this.loadPORequest();
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

	loadPORequest() {
		this.data = null;
		this.total = 0;

		this.api.sales.getProjectPORequest(this.projectId, response => {
			this.data = response.data;

			// Prepare supplier records for aggregation
			const supplierIndex = Mangler.index(this.data.suppliers, 'id');
			this.data.suppliers.forEach(m => {
				m.total = 0;
				m.products = [];
			});

			// Set up empty object for products with unknown suppliers
			const unknown = {
				id: 0,
				name: 'Unknown',
				total: 0,
				products: []
			}
			supplierIndex[0] = unknown;

			// Process products
			this.data.products.forEach(p => {
				const m = supplierIndex[p.supplier_id || 0] || supplierIndex[0];
				m.total += p.total;
				this.total += p.total;
				m.products.push(p);
			});

			if (unknown.products.length) {
				this.data.suppliers.unshift(unknown);
			}

			setTimeout(() => {
				this.app.header.setTab('po-request');
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

}
