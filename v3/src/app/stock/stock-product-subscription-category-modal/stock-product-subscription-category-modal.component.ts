import { ModalService } from './../../shared/modal/modal.service';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { ModalComponent } from './../../shared/modal/modal.component';
import { Component, ViewChild } from '@angular/core';

@Component({
	selector: 'app-stock-product-subscription-category-modal',
	templateUrl: './stock-product-subscription-category-modal.component.html'
})
export class StockProductSubscriptionCategoryModalComponent {

	@ViewChild(ModalComponent) modal: ModalComponent;

	buttons = ['0|Cancel', '1|*Save'];
	data;

	constructor(
		private app: AppService,
		private api: ApiService,
		private modalService: ModalService
	) {
		const mdata = this.modalService.data;
		if (!mdata) return;

		if (mdata.id === 'new') {
			this.data = {
				details: {
					id: 'new',
					owner_level: mdata.owner ? mdata.owner.owner_level : '',
					owner_id: mdata.owner ? mdata.owner.owner_id : ''
				},
				item_count: 0
			};
		} else {
			this.api.products.getSubscriptionCategory(mdata.id, response => {
				this.data = response.data;
				if (!this.data.item_count) {
					this.buttons.push('2|<!Delete');
					this.buttons = this.buttons.slice();
				}
			}, response => {
				this.app.notifications.showDanger(response.message);
			});
		}
	}

	modalHandler(event) {
		if (!event.data) {
			this.modal.close();
			return;
		}

		switch (event.data.id) {
			case 1:
				this.api.products.saveSubscriptionCategory(this.data.details, () => {
					this.app.notifications.showSuccess(this.data.details.id === 'new' ? 'Subscription category created.' : 'Subscription category updated.');
					this.modal.close();
				}, response => {
					this.app.notifications.showDanger(response.message);
				});
				break;

			case 2:
				this.api.products.deleteSubscriptionCategory(this.data.details.id, () => {
					this.app.notifications.showSuccess('Subscription category deleted.');
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
