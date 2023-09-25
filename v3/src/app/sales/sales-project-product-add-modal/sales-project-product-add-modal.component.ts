import { ModalService } from './../../shared/modal/modal.service';
import { ApiService } from './../../api.service';
import { ModalComponent } from './../../shared/modal/modal.component';
import { Component, OnInit, ViewChild } from '@angular/core';
import { Pagination } from 'app/shared/pagination';

declare var Mangler: any;

@Component({
	selector: 'app-sales-project-product-add-modal',
	templateUrl: './sales-project-product-add-modal.component.html',
	styleUrls: ['./sales-project-product-add-modal.component.less']
})
export class SalesProjectProductAddModalComponent implements OnInit {

	@ViewChild(ModalComponent) modal: ModalComponent;

	modules: any[] = [];
	systems: any[] = [];
	selectedSystem = null;
	addToToolbox = true;
	owner = '';

	list: any[] = [];
	search = '';
	pagination = new Pagination();

	constructor(
		private api: ApiService,
		private modalService: ModalService
	) {
		this.modules = this.modalService.data.moduleList || [];
		this.systems = this.modalService.data.systemList || [];
		this.selectedSystem = this.modalService.data.system || null;
		this.owner = this.modalService.data.owner || '';

		// Add systems to modules
		this.modules.forEach(m => m.systems = []);
		const moduleIndex = Mangler.index(this.modules, 'id');
		this.systems.forEach(s => {
			const m = moduleIndex[s.module_id];
			if (m) m.systems.push(s);
		});

		if (this.systems.length === 0) {
			this.addToToolbox = false;
		} else if (!this.selectedSystem) {
			this.selectedSystem = this.systems[0].id;
		}
	}

	ngOnInit() {
		this.api.products.listProducts({
			sold_to_customer: 1,
			product_owner: this.owner
		}, response => {
			this.list = response.data.list;
		});
	}

	selectItem(item) {
		this.modal.close({
			product: item,
			system: this.selectedSystem,
			toolbox: this.addToToolbox
		});
	}

	modalHandler(event) {
		this.modal.close();
	}

}
