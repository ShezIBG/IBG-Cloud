import { ModalService } from './../../shared/modal/modal.service';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { ModalComponent } from './../../shared/modal/modal.component';
import { Component, OnInit, ViewChild } from '@angular/core';

@Component({
	selector: 'app-lighting-add-schedule-modal',
	templateUrl: './lighting-add-schedule-modal.component.html'
})
export class LightingAddScheduleModalComponent implements OnInit {

	@ViewChild(ModalComponent) modal;

	data;
	record;

	constructor(
		private app: AppService,
		private api: ApiService,
		private modalService: ModalService
	) { }

	ngOnInit() {
		this.data = this.modalService.data;
		this.record = {
			description: ''
		};
	}

	modalEvent(event) {
		if (!event.data || !event.data.id || event.data.id === 0) {
			this.modal.close();
			return;
		}

		switch (event.data.id) {
			case 1:
				// Add schedule
				this.api.lighting.addSchedule(this.data.building_id, this.record, response => {
					this.app.notifications.showSuccess('New schedule added.');
					this.modal.close(response.data);
				}, response => {
					this.app.notifications.showDanger(response.message);
				});
				break;
		}
	}

}
