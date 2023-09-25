import { BundleOptions } from './../../shared/bundle-options';
import { ModalService } from './../../shared/modal/modal.service';
import { Pagination } from './../../shared/pagination';
import { ModalComponent } from './../../shared/modal/modal.component';
import { Component, OnInit, ViewChild } from '@angular/core';
import { AppService } from 'app/app.service';
import { ApiService } from 'app/api.service';

declare var Mangler: any;

@Component({
	selector: 'app-stock-bundle-counter-edit-modal',
	templateUrl: './stock-bundle-counter-edit-modal.component.html',
	styleUrls: ['./stock-bundle-counter-edit-modal.component.less']
})
export class StockBundleCounterEditModalComponent implements OnInit {

	@ViewChild(ModalComponent) modal: ModalComponent;

	tabs: any[] = [
		{ id: 'details', description: 'Accumulator details' },
		{ id: 'products', description: 'Add product' }
	];
	selectedTab = 'details';
	title = '';
	buttons = ['0|Cancel', '1|*Save'];

	listProducts: any[] = [];
	search = '';
	pagination = new Pagination();

	owner;
	bundle: BundleOptions;
	counter: any;

	newVersion = false;

	constructor(
		private app: AppService,
		private api: ApiService,
		private modalService: ModalService
	) { }

	ngOnInit() {
		this.owner = this.modalService.data.owner;
		this.bundle = this.modalService.data.bundle;
		this.counter = this.modalService.data.counter;
		this.title = this.counter.description;
	}

	selectTab(id) {
		this.selectedTab = id;

		if (id === 'products') {
			this.api.products.listProducts({
				product_owner: this.owner,
				is_placeholder: 0,
				is_bundle: 0
			}, response => {
				this.listProducts = response.data.list;
			}, response => {
				this.app.notifications.showDanger(response.message);
			});
		}
	}

	hasProduct(id) {
		return !!Mangler.findOne(this.counter.products, { product_id: id });
	}

	addProduct(item) {
		if (!this.hasProduct(item.id)) {
			this.counter.products.push(this.bundle.getNewCounterProductData(this.counter, item));
			this.counter.products = this.counter.products.slice();
		}
	}

	modalHandler(event) {
		if (event.data && event.data.id === 1) {
			if (this.counter.counter_id) {
				this.bundle.updateCounter(this.counter);
				if (this.newVersion) this.bundle.requireNewVersion();
			} else {
				this.bundle.addCounter(this.counter);
			}
		}

		this.modal.close();
	}

	formatProductNumbers(item) {
		item.quantity = parseFloat(item.quantity) || 0;
		item.range_start = parseInt(item.range_start, 10) || 0;
		item.range_end = parseInt(item.range_end, 10) || 0;
	}

	removeProduct(item) {
		const i = this.counter.products.indexOf(item);
		if (i !== -1) {
			this.counter.products.splice(i, 1);
			this.counter.products = this.counter.products.slice();
			this.newVersion = true;
		}
	}

}
