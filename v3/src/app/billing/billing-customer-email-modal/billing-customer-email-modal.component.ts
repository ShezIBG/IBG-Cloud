import { ModalService } from './../../shared/modal/modal.service';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { ModalComponent } from './../../shared/modal/modal.component';
import { Component, OnInit, ViewChild } from '@angular/core';

@Component({
	selector: 'app-billing-customer-email-modal',
	templateUrl: './billing-customer-email-modal.component.html'
})
export class BillingCustomerEmailModalComponent implements OnInit {

	@ViewChild(ModalComponent) modal;

	data;
	info;
	selected = null;
	disabled = false;

	constructor(
		private app: AppService,
		private api: ApiService,
		private modalService: ModalService
	) { }

	ngOnInit() {
		this.data = this.modalService.data;

		if (!this.data.owner_type && !this.data.owner_id && this.data.owner) {
			const chunks = ('' + this.data.owner).split('-');
			this.data.owner_type = chunks[0] || '';
			this.data.owner_id = chunks[1] || '';
		}

		this.api.settings.listEmailTemplates(this.data.owner_type, this.data.owner_id, response => {
			this.info = response.data;
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	modalEvent(event) {
		if (!event.data || !event.data.id || event.data.id === 0) {
			this.modal.close();
			return;
		}

		switch (event.data.id) {
			case 1:
				// Send email
				this.disabled = true;
				this.api.billing.sendCustomerEmail({
					owner_type: this.data.owner_type,
					owner_id: this.data.owner_id,
					template_type: this.selected ? this.selected.id : null,
					customers: this.data.customers
				}, () => {
					this.disabled = false;
					this.app.notifications.showSuccess(this.data.customers.length === 1 ? 'Email has been sent.' : 'Emails have been sent.');
					this.modal.close();
				}, response => {
					this.disabled = false;
					this.app.notifications.showDanger(response.message);
				});
				break;
		}
	}

}
