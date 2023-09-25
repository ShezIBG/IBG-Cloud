import { AppService } from '../../app.service';
import { ApiService } from '../../api.service';
import { ModalEvent, ModalComponent } from '../../shared/modal/modal.component';
import { ModalService } from '../../shared/modal/modal.service';
import { Component, ViewChild } from '@angular/core';
import { KnxValue } from '../knx-value';
import { Util } from 'app/shared/util';

@Component({
	selector: 'app-control-device-details-modal',
	templateUrl: './control-device-details-modal.component.html'
})
export class ControlDeviceDetailsModalComponent {

	@ViewChild(ModalComponent) modal: ModalComponent;

	buildingId = null;
	id = null;
	data: any;
	tab = 'details';
	buttons = ['0|Close'];

	newHold = null;
	originalSchedule = null;

	knx = {};

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
		this.api.control.getDeviceDetails(this.buildingId, this.id, data => {
			this.data = data.data;
			this.originalSchedule = this.data.info.weekly_schedule_id;

			// Create KNX value objects
			this.knx = {};
			this.data.knx.forEach(item => {
				this.knx[item.id] = new KnxValue(item.id, item.knx_datatype, item.knx_subtype, item.value, item.is_readonly);
			});

			this.refreshButtons();
		});
	}

	modalHandler(event: ModalEvent) {
		if (event.data) {
			switch (event.data.id) {
				case 1:
					const send = [];
					this.data.knx.forEach(item => {
						const knxValue = this.knx[item.id];
						if (knxValue) {
							if (item.value !== knxValue.value) send.push({ id: knxValue.id, datatype: knxValue.dataType, value: knxValue.value });
						}
					});

					if (this.data.info.weekly_schedule_id !== this.originalSchedule) {
						this.api.control.updateDeviceDetails(this.buildingId, this.id, this.data.info.weekly_schedule_id, () => {
							this.api.control.sendKnxValues({
								building_id: this.buildingId,
								knx: send
							}, () => {
								this.app.notifications.showSuccess('Device updated.');
								this.modal.close();
							}, response => {
								this.app.notifications.showDanger(response.message);
							});
						}, response => {
							this.app.notifications.showDanger(response.message);
						});
					} else {
						this.api.control.sendKnxValues({
							building_id: this.buildingId,
							knx: send
						}, () => {
							this.app.notifications.showSuccess('Device updated.');
							this.modal.close();
						}, response => {
							this.app.notifications.showDanger(response.message);
						});
					}


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
		if (this.tab === 'details' && this.isDirty()) {
			this.buttons = ['1|*Update', '0|Close'];
		} else {
			this.buttons = ['0|Close'];
		}
	}

	isDirty() {
		let dirty = false;

		if (this.originalSchedule !== this.data.info.weekly_schedule_id) {
			return true;
		} else {
			this.data.knx.forEach(item => {
				if (this.knx[item.id]) {
					if (item.value !== this.knx[item.id].value) dirty = true;
				}
			});
		}

		return dirty;
	}

	isMobile() {
		return Util.isMobile;
	}

}
