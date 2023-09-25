import { DecimalPipe } from './../../shared/decimal.pipe';
import { ModalService } from './../../shared/modal/modal.service';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { ModalComponent } from './../../shared/modal/modal.component';
import { Component, ViewChild } from '@angular/core';

@Component({
	selector: 'app-settings-payment-gateway-edit-modal',
	templateUrl: './settings-payment-gateway-edit-modal.component.html'
})
export class SettingsPaymentGatewayEditModalComponent {

	@ViewChild(ModalComponent) modal: ModalComponent;

	id;
	data;
	title;
	buttons;

	partMinimum = '';

	get allowPartPayment() { return !!this.data.allow_part_payment; }
	set allowPartPayment(value) { this.data.allow_part_payment = value ? 1 : 0; }

	constructor(
		private app: AppService,
		private api: ApiService,
		private modalService: ModalService
	) {
		this.id = this.modalService.data.id;
		this.data = this.modalService.data.data;

		this.partMinimum = DecimalPipe.transform(this.data.part_minimum_pence / 100, 2, 2, false);

		switch (this.data.type) {
			case 'gocardless':
				this.title = 'GoCardless account';
				break;
			case 'stripe':
				this.title = 'Stripe account';
				break;
		}

		if (this.id === 'new') {
			this.buttons = ['0|Cancel', '1|*Create and link account']
		} else {
			this.buttons = ['0|Cancel', '1|*Update']
		}
	}

	modalHandler(event) {
		if (!event.data) {
			this.modal.close();
			return;
		}

		switch (event.data.id) {
			case 1:
				const savedButtons = this.buttons;
				this.buttons = ['0|Cancel'];

				this.api.settings.savePaymentGateway(this.id, this.data, response => {
					if (this.id === 'new') {
						const newId = response.data;
						this.api.settings.authorisePaymentGateway(newId, urlResponse => {
							this.app.redirect(urlResponse.data);
						}, urlResponse => {
							this.app.notifications.showDanger(urlResponse.message);
							this.modal.close();
						});
					} else {
						this.app.notifications.showSuccess('Payment gateway updated.');
						this.modal.close();
					}
				}, response => {
					this.app.notifications.showDanger(response.message);
					this.buttons = savedButtons;
				});
				break;

			default:
				this.modal.close();
				break;

		}
	}

	formatNumbers() {
		this.data.part_minimum_pence = Math.round(parseFloat(this.partMinimum) * 100) || 0;
		if (this.data.part_minimum_pence < 0) this.data.part_minimum_pence = 0;

		this.partMinimum = DecimalPipe.transform(this.data.part_minimum_pence / 100, 2, 2, false);
	}

}
