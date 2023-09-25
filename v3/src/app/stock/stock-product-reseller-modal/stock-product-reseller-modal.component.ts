import { ModalService } from './../../shared/modal/modal.service';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { ModalComponent } from './../../shared/modal/modal.component';
import { Component, ViewChild } from '@angular/core';

@Component({
	selector: 'app-stock-product-reseller-modal',
	templateUrl: './stock-product-reseller-modal.component.html'
})
export class StockProductResellerModalComponent {

	@ViewChild(ModalComponent) modal: ModalComponent;

	buttons = ['0|Cancel', '1|*Save'];
	data;

	constructor(
		private app: AppService,
		private api: ApiService,
		private modalService: ModalService
	) {
		this.data = modalService.data;

		if (!this.data.new) {
			this.buttons.push('2|<!Delete');
			this.buttons = this.buttons.slice();
		}
	}

	modalHandler(event) {
		if (!event.data) {
			this.modal.close();
			return;
		}

		switch (event.data.id) {
			case 1:
				this.api.products.saveReseller(this.data.details, () => {
					this.app.notifications.showSuccess('Reseller saved.');
					this.modal.close();
				}, response => {
					this.app.notifications.showDanger(response.message);
				});
				break;

			case 2:
				this.api.products.deleteReseller(this.data.details.owner, this.data.details.reseller, () => {
					this.app.notifications.showSuccess('Reseller deleted.');
					this.modal.close();
				}, response => {
					this.app.notifications.showDanger(response.message);
				});
				break;

			default:
				this.modal.close();
				break;

		}
	}

}
