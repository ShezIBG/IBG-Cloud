import { AppService } from '../../app.service';
import { ApiService } from '../../api.service';
import { ModalEvent, ModalComponent } from '../../shared/modal/modal.component';
import { ModalService } from '../../shared/modal/modal.service';
import { Component, ViewChild } from '@angular/core';

@Component({
	selector: 'app-lighting-device-details-modal',
	templateUrl: './lighting-device-details-modal.component.html'
})
export class LightingDeviceDetailsModalComponent {

	@ViewChild(ModalComponent) modal: ModalComponent;

	buildingId = null;
	id = null;
	data: any;
	tab = 'details';
	buttons = ['0|Close'];

	newHold = null;
	originalSchedule = null;
	originalLights = 0;

	constructor(
		private app: AppService,
		private api: ApiService,
		private modalService: ModalService
	) {
		// Read data
		this.buildingId = this.modalService.data.building_id;
		this.id = this.modalService.data.id;
		this.reloadData();
	}

	reloadData() {
		this.api.lighting.getDeviceDetails(this.buildingId, this.id, data => {
			this.data = data.data;
			this.originalSchedule = this.data.record.weekly_schedule_id;
			this.originalLights = this.data.record.no_of_lights;
			this.refreshButtons();
		});
	}

	modalHandler(event: ModalEvent) {
		if (event.data) {
			switch (event.data.id) {
				case 1:
					this.api.lighting.updateDeviceDetails(this.buildingId, this.id, this.data.record.weekly_schedule_id, this.data.record.no_of_lights, () => {
						this.app.notifications.showSuccess('Schedule updated.');
						this.modal.close();
					}, response => {
						this.app.notifications.showDanger(response.message);
					});
					break;

				default:
					event.modal.close();
					break;

			}
		} else {
			event.modal.close();
		}
	}

	setTab(tab) {
		this.tab = tab;
		this.refreshButtons();
	}

	refreshButtons() {
		if (this.tab === 'details' && (this.data.record.weekly_schedule_id !== this.originalSchedule || this.data.record.no_of_lights !== this.originalLights)) {
			this.buttons = ['1|*Update device', '0|Close'];
		} else {
			this.buttons = ['0|Close'];
		}
	}

}
