import { AppService } from './../../app.service';
import { ApiService } from './../../api.service';
import { ModalEvent, ModalComponent } from './../../shared/modal/modal.component';
import { ModalService } from './../../shared/modal/modal.service';
import { Component, ViewChild } from '@angular/core';

declare var Mangler: any;

@Component({
	selector: 'app-relay-device-details-modal',
	templateUrl: './relay-device-details-modal.component.html'
})
export class RelayDeviceDetailsModalComponent {

	@ViewChild(ModalComponent) modal: ModalComponent;

	id = null;
	data: any;
	tab = 'details';
	buttons = ['0|Close'];

	originalSchedule = null;

	constructor(
		private app: AppService,
		private api: ApiService,
		private modalService: ModalService
	) {
		// Read data
		this.id = this.modalService.data;
		this.reloadData();
	}

	reloadData() {
		this.api.relay.getDeviceDetails(this.id, data => {
			this.data = data.data;
			this.originalSchedule = this.data.record.weekly_schedule_id;
			this.refreshButtons();
		});
	}

	modalHandler(event: ModalEvent) {
		if (event.data) {
			switch (event.data.id) {
				case 1:
					this.api.relay.updateDeviceSchedule(this.id, this.data.record.weekly_schedule_id, () => {
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

	getFlagIconClass(flag, errorIcon = 'md md-error') {
		if (flag === 0) {
			return 'status-icon md md-help text-warning';
		} else if (flag === 1) {
			return 'status-icon md md-check text-success';
		} else {
			return 'status-icon ' + errorIcon + ' text-danger';
		}
	}

	getNullFlagIconClass(flag, errorIcon = 'md md-error') {
		if (flag === null) {
			return 'status-icon md md-help text-warning';
		} else if (flag === 0) {
			return 'status-icon md md-check text-success';
		} else {
			return 'status-icon ' + errorIcon + ' text-danger';
		}
	}

	refreshButtons() {
		if (this.tab === 'details' && this.data.record.weekly_schedule_id !== this.originalSchedule) {
			this.buttons = ['1|*Update schedule', '0|Close'];
		} else {
			this.buttons = ['0|Close'];
		}
	}

}
