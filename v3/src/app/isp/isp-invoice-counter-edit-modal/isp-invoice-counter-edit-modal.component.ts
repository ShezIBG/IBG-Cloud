import { ModalService } from './../../shared/modal/modal.service';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { ModalComponent } from './../../shared/modal/modal.component';
import { Component, OnInit, ViewChild } from '@angular/core';

@Component({
	selector: 'app-isp-invoice-counter-edit-modal',
	templateUrl: './isp-invoice-counter-edit-modal.component.html'
})
export class IspInvoiceCounterEditModalComponent implements OnInit {

	@ViewChild(ModalComponent) modal;

	get nextNo() {
		return this.lastNo + 1;
	}

	get lastNo() {
		return parseInt(this.record.last_no, 10) || 0;
	}

	get originalLastNo() {
		return parseInt(this.original_last_no, 10) || 0;
	}

	data;
	record;
	original_last_no;

	constructor(
		private app: AppService,
		private api: ApiService,
		private modalService: ModalService
	) { }

	ngOnInit() {
		this.data = this.modalService.data;
		this.api.isp.getInvoiceCounter(this.data.owner_type, this.data.owner_id, response => {
			this.record = response.data;
			this.original_last_no = this.record.last_no;
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
				// Save counter
				this.api.isp.saveInvoiceCounter(this.record, () => {
					this.app.notifications.showSuccess('Invoice counter updated.');
					this.modal.close();
				}, response => {
					this.app.notifications.showDanger(response.message);
				});
				break;
		}
	}

}
