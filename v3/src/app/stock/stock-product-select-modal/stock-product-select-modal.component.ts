import { Pagination } from './../../shared/pagination';
import { ModalService } from './../../shared/modal/modal.service';
import { ApiService } from './../../api.service';
import { ModalComponent } from './../../shared/modal/modal.component';
import { Component, OnInit, ViewChild } from '@angular/core';

@Component({
	selector: 'app-stock-product-select-modal',
	templateUrl: './stock-product-select-modal.component.html',
	styleUrls: ['./stock-product-select-modal.component.less']
})
export class StockProductSelectModalComponent implements OnInit {

	@ViewChild(ModalComponent) modal: ModalComponent;

	list: any[] = [];
	search = '';
	pagination = new Pagination();

	constructor(
		private api: ApiService,
		private modalService: ModalService
	) { }

	ngOnInit() {
		this.api.products.listProducts(this.modalService.data, response => {
			this.list = response.data.list;
		});
	}

	selectItem(item) {
		this.modal.close(item);
	}

	modalHandler(event) {
		this.modal.close();
	}

}
