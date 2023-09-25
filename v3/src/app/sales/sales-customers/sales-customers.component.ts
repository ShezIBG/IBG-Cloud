import { Pagination } from 'app/shared/pagination';
import { AppService } from './../../app.service';
import { ApiService } from './../../api.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-sales-customers',
	templateUrl: './sales-customers.component.html'
})
export class SalesCustomersComponent implements OnInit, OnDestroy {

	list: any = [];
	si = false;
	search = '';
	pagination = new Pagination();

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
		this.api.sales.listCustomers(this.app.selectedProductOwner, response => {
			this.list = response.data.list || [];
			this.si = response.data.si;
			this.app.resolveProductOwners(response);
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

}
