import { ModalService } from 'app/shared/modal/modal.service';
import { Pagination } from 'app/shared/pagination';
import { ModalComponent } from 'app/shared/modal/modal.component';
import { Component, OnInit, ViewChild } from '@angular/core';
import { ApiService } from 'app/api.service';

@Component({
	selector: 'app-stock-entity-select-modal',
	templateUrl: './stock-entity-select-modal.component.html'
})
export class StockEntitySelectModalComponent implements OnInit {

	@ViewChild(ModalComponent) modal: ModalComponent;

	title = '';
	list: any[] = [];
	search = '';
	pagination = new Pagination();

	constructor(
		private api: ApiService,
		private modalService: ModalService
	) { }

	ngOnInit() {
		const data = this.modalService.data;

		this.title = 'Select Entity';
		if (data.filters.is_manufacturer) this.title = 'Select Manufacturer';
		if (data.filters.is_supplier) this.title = 'Select Supplier';

		this.api.products.listEntities(data.owner, data.filters, response => {
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
