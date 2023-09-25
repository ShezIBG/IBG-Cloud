import { Component, OnInit, ViewChild } from '@angular/core';
import { ApiService } from 'app/api.service';
import { AppService } from 'app/app.service';
import { ModalComponent } from 'app/shared/modal/modal.component';
import { ModalService } from 'app/shared/modal/modal.service';

@Component({
	selector: 'app-isp-area-note-modal',
	templateUrl: './isp-area-note-modal.component.html',
})
export class IspAreaNoteModalComponent implements OnInit {

	@ViewChild(ModalComponent) modal;

	id;
	notes;

	constructor(
		private app: AppService,
		private api: ApiService,
		private modalService: ModalService
	) { }

	ngOnInit() {
		this.id = this.modalService.data.id;
		this.notes = this.modalService.data.notes;
	}

	modalEvent(event) {
		if (!event.data || !event.data.id || event.data.id === 0) {
			this.modal.close();
			return;
		}

		switch (event.data.id) {
			case 1:
				// Save notes
				this.api.isp.updateAreaNote({
					id: this.id,
					notes: this.notes
				}, () => {
					this.app.notifications.showSuccess('Area notes updated.');
					this.modal.close();
				}, response => {
					this.app.notifications.showDanger(response.message);
				});
				break;
		}
	}

}
