import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnDestroy } from '@angular/core';

declare var Mangler: any;
declare var window: any;

@Component({
	selector: 'app-sales-project-itemised-quotation',
	templateUrl: './sales-project-itemised-quotation.component.html',
	styleUrls: ['./sales-project-itemised-quotation.component.less']
})
export class SalesProjectItemisedQuotationComponent implements OnDestroy {

	projectId;
	data;
	total = 0;
	projectAddress = [];
	customerAddress = [];

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

		this.api.sales.getProjectItemisedQuotation(this.projectId, response => {
			this.data = response.data;

			// Assemble address arrays
			this.projectAddress = [];
			if (this.data.project.address_line_1) this.projectAddress.push(this.data.project.address_line_1);
			if (this.data.project.address_line_2) this.projectAddress.push(this.data.project.address_line_2);
			if (this.data.project.address_line_3) this.projectAddress.push(this.data.project.address_line_3);
			if (this.data.project.posttown) this.projectAddress.push(this.data.project.posttown);
			if (this.data.project.postcode) this.projectAddress.push(this.data.project.postcode);

			this.customerAddress = [];
			if (this.data.customer.address_line_1) this.customerAddress.push(this.data.customer.address_line_1);
			if (this.data.customer.address_line_2) this.customerAddress.push(this.data.customer.address_line_2);
			if (this.data.customer.address_line_3) this.customerAddress.push(this.data.customer.address_line_3);
			if (this.data.customer.posttown) this.customerAddress.push(this.data.customer.posttown);
			if (this.data.customer.postcode) this.customerAddress.push(this.data.customer.postcode);

			// Process products
			this.data.products.forEach(p => {
				this.total += p.total;
			});

			setTimeout(() => {
				this.app.header.setTab('itemised-quotation');
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
