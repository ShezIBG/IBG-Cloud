import { ModalService } from './../../shared/modal/modal.service';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { ModalComponent } from './../../shared/modal/modal.component';
import { Component, OnInit, ViewChild } from '@angular/core';

@Component({
	selector: 'app-isp-invoice-no-edit-modal',
	templateUrl: './isp-invoice-no-edit-modal.component.html'
})
export class IspInvoiceNoEditModalComponent implements OnInit {

	@ViewChild(ModalComponent) modal;

	data;
	invoice;

	constructor(
		private app: AppService,
		private api: ApiService,
		private modalService: ModalService
	) { }

	ngOnInit() {
		this.data = this.modalService.data;
		this.api.isp.getInvoice(this.data.invoice_id, response => {
			this.invoice = response.data.invoice;
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
				// Save invoice number
				this.api.isp.setInvoiceNo(this.invoice.id, this.invoice.invoice_no, () => {
					this.app.notifications.showSuccess('Invoice number updated.');
					this.modal.close();
				}, response => {
					this.app.notifications.showDanger(response.message);
				});
				break;
		}
	}

}
