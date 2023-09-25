import { MySQLDateToISOPipe } from './../../shared/mysql-date-to-iso.pipe';
import { AppService } from './../../app.service';
import { ApiService } from './../../api.service';
import { ModalEvent, ModalComponent } from './../../shared/modal/modal.component';
import { ModalService } from './../../shared/modal/modal.service';
import { Component, ViewChild } from '@angular/core';

declare var Mangler: any;

@Component({
	selector: 'app-climate-device-details-modal',
	templateUrl: './climate-device-details-modal.component.html'
})
export class ClimateDeviceDetailsModalComponent {

	@ViewChild(ModalComponent) modal: ModalComponent;

	id = null;
	data: any;
	tab = 'details';
	buttons = ['0|Close'];

	modeOptions = [
		{ id: null, desc: '' },
		{ id: 'cool', desc: 'Cool' },
		{ id: 'heat', desc: 'Heat' },
		{ id: 'auto', desc: 'Auto' },
		{ id: 'dry', desc: 'Dry' },
		{ id: 'haux', desc: 'Haux' },
		{ id: 'fan', desc: 'Fan' }
	];

	fanOptions = [
		{ id: null, desc: '' },
		{ id: 'very_low', desc: 'Very low' },
		{ id: 'low', desc: 'Low' },
		{ id: 'medium', desc: 'Medium' },
		{ id: 'high', desc: 'High' },
		{ id: 'top', desc: 'Top' },
		{ id: 'auto', desc: 'Auto' }
	];

	louvreOptions = [
		{ id: null, desc: '' },
		{ id: 'vertical', desc: 'Vertical' },
		{ id: '30', desc: '30&deg;' },
		{ id: '45', desc: '45&deg;' },
		{ id: '60', desc: '60&deg;' },
		{ id: 'horizontal', desc: 'Horizontal' },
		{ id: 'auto', desc: 'Auto' },
		{ id: 'off', desc: 'Off' }
	];

	modeIndex;
	fanIndex;
	louvreIndex;

	newHold = null;
	originalSchedule = null;

	constructor(
		private app: AppService,
		private api: ApiService,
		private modalService: ModalService
	) {
		// Prepare lookups
		this.modeIndex = Mangler.index(this.modeOptions, 'id');
		this.fanIndex = Mangler.index(this.fanOptions, 'id');
		this.louvreIndex = Mangler.index(this.louvreOptions, 'id');

		// Read data
		this.id = this.modalService.data;
		this.reloadData();
	}

	reloadData() {
		this.api.climate.getDeviceDetails(this.id, data => {
			this.data = data.data;
			this.originalSchedule = this.data.record.weekly_schedule_id;
			this.refreshButtons();
		});
	}

	modalHandler(event: ModalEvent) {
		if (event.data) {
			switch (event.data.id) {
				case 1:
					this.api.climate.updateDeviceSchedule(this.id, this.data.record.weekly_schedule_id, response => {
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

	addHold() {
		this.newHold = {
			datetime_start: null,
			datetime_end: null,
			coolhub_id: this.data.record.coolhub_id,
			coolplug_id: this.data.record.coolplug_id,
			coolplug_table_id: this.data.record.id,
			ac_setpoint: null,
			ac_onoff: 1,
			ac_mode: null,
			ac_fanspeed: null,
			ac_swing: null
		}
	}

	deleteHold(holdId) {
		if (confirm('Are you sure you want to delete this temporary hold?')) {
			this.api.climate.deleteHold(holdId, () => {
				this.reloadData();
				this.app.notifications.showSuccess('Hold deleted.');
			}, response => {
				this.app.notifications.showDanger(response.message);
			});
		}
	}

	saveHold() {
		const holdData = Mangler.clone(this.newHold);
		holdData.datetime_start = MySQLDateToISOPipe.dateToString(holdData.datetime_start);
		holdData.datetime_end = MySQLDateToISOPipe.dateToString(holdData.datetime_end);

		this.api.climate.createHold(holdData, () => {
			this.reloadData();
			this.newHold = null;
			this.app.notifications.showSuccess('Hold added.');
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	refreshButtons() {
		if (this.tab === 'details' && this.data.record.weekly_schedule_id !== this.originalSchedule) {
			this.buttons = ['1|*Update schedule', '0|Close'];
		} else {
			this.buttons = ['0|Close'];
		}
	}

}
