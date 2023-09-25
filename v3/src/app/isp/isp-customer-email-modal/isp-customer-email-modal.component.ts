import { ModalService } from './../../shared/modal/modal.service';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { ModalComponent } from './../../shared/modal/modal.component';
import { Component, OnInit, ViewChild } from '@angular/core';

@Component({
	selector: 'app-isp-customer-email-modal',
	templateUrl: './isp-customer-email-modal.component.html'
})
export class IspCustomerEmailModalComponent implements OnInit {

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
				this.api.isp.sendCustomerEmail({
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
