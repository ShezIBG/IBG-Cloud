import { ModalService } from './../../shared/modal/modal.service';
import { ModalComponent } from './../../shared/modal/modal.component';
import { Component, ViewChild } from '@angular/core';

export interface ProductCloneFlags {
	accessories: boolean,
	alternatives: boolean,
	bom: boolean,
	placeholders: boolean,
	labour: boolean,
	subscription: boolean,
	warehouses: boolean,
	bundle: boolean
}

export interface ProductCloneOptions {
	id: string,
	sku: string,
	model: string,
	short_description: string,
	long_description: string,
	clone: ProductCloneFlags,
	allowed: ProductCloneFlags
};

@Component({
	selector: 'app-stock-product-clone-modal',
	templateUrl: './stock-product-clone-modal.component.html'
})
export class StockProductCloneModalComponent {

	@ViewChild(ModalComponent) modal: ModalComponent;

	options: ProductCloneOptions = null;

	constructor(private modalService: ModalService) {
		this.options = this.modalService.data;
	}

	modalHandler(event) {
		if (event.type === 'button' && event.data && event.data.id === 1) {
			this.modal.close(this.options);
		} else {
			this.modal.close();
		}
	}

}
