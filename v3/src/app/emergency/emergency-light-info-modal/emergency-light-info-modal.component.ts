import { AppService } from './../../app.service';
import { MySQLDateToISOPipe } from './../../shared/mysql-date-to-iso.pipe';
import { ApiService } from './../../api.service';
import { ModalEvent, ModalComponent } from './../../shared/modal/modal.component';
import { ModalService } from './../../shared/modal/modal.service';
import { Component, ViewChild } from '@angular/core';

declare var $: any;

@Component({
	selector: 'app-emergency-light-info-modal',
	templateUrl: './emergency-light-info-modal.component.html',
	styleUrls: ['./emergency-light-info-modal.component.less']
})
export class EmergencyLightInfoModalComponent {

	@ViewChild(ModalComponent) modal: ModalComponent;
	@ViewChild('plan') plan;
	@ViewChild('zoomin') zoomin;
	@ViewChild('zoomout') zoomout;

	id = null;
	data: any;
	tab = 'details';

	$panzoom = null;
	scale = 1;

	history = [];
	manual_function_datetime = null;
	manual_duration_datetime = null;
	has_manual_function_datetime = false;
	has_manual_duration_datetime = false;
	is_repaired = false;
	repair_notes = '';

	saving = false;

	constructor(
		private app: AppService,
		private api: ApiService,
		private modalService: ModalService
	) {
		this.id = this.modalService.data;
		api.emergency.getLight(this.id, data => {
			this.data = data.data;

			this.history = [];
			this.data.test_history.forEach(test => {
				test.history_type = 'test';
				test.datetime = MySQLDateToISOPipe.transform(test.datetime);
				this.history.push(test);
			});
			this.data.history.forEach(log => {
				log.history_type = 'log';
				log.datetime = MySQLDateToISOPipe.transform(log.datetime);
				this.history.push(log);
			});

			this.history.sort((a, b) => {
				if (a.datetime > b.datetime) return -1;
				if (a.datetime < b.datetime) return 1;
				return 0;
			});

			this.manual_function_datetime = MySQLDateToISOPipe.stringToDate(this.data.light.manual_function_datetime);
			this.manual_duration_datetime = MySQLDateToISOPipe.stringToDate(this.data.light.manual_duration_datetime);
			this.has_manual_function_datetime = !!this.manual_function_datetime;
			this.has_manual_duration_datetime = !!this.manual_duration_datetime;

			// If there is no manual date/time set, generate one from original schedule
			const now = new Date();
			const nowString = MySQLDateToISOPipe.dateToString(now);
			if (!this.has_manual_function_datetime && this.data.light.scheduled_function_datetime) {
				try {
					const newSchedule = MySQLDateToISOPipe.stringToDate(nowString.split(' ')[0] + ' ' + this.data.light.scheduled_function_datetime.split(' ')[1]);
					if (newSchedule < now) newSchedule.setUTCDate(newSchedule.getUTCDate() + 1);
					this.manual_function_datetime = newSchedule;
				} catch (ex) { }
			}
			if (!this.has_manual_duration_datetime && this.data.light.scheduled_duration_datetime) {
				try {
					const newSchedule = MySQLDateToISOPipe.stringToDate(nowString.split(' ')[0] + ' ' + this.data.light.scheduled_duration_datetime.split(' ')[1]);
					if (newSchedule < now) newSchedule.setUTCDate(newSchedule.getUTCDate() + 1);
					this.manual_duration_datetime = newSchedule;
				} catch (ex) { }
			}

			// Check schedule boxes by default if tests are failing and we have generated new schedule date
			if (this.data.light.function_test_status === -1 && this.manual_function_datetime) this.has_manual_function_datetime = true;
			if (this.data.light.duration_test_status === -1 && this.manual_duration_datetime) this.has_manual_duration_datetime = true;
		});
	}

	modalHandler(event: ModalEvent) {
		event.modal.close();
	}

	setTab(tab) {
		this.tab = tab;
		if (this.tab === 'location') {
			setTimeout(() => this.initPanzoom(), 0);
		}
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

	refreshScale() {
		this.scale = this.$panzoom.panzoom('instance').scale;
	}

	initPanzoom() {
		if (!this.plan) return;

		const context = this;

		this.$panzoom = $(this.plan.nativeElement).panzoom({
			onZoom: (e, panzoom) => {
				this.refreshScale();
			},
			$zoomIn: $(this.zoomin.nativeElement),
			$zoomOut: $(this.zoomout.nativeElement)
		});

		this.$panzoom.parent().off('mousewheel.focal').on('mousewheel.focal', e => {
			e.preventDefault();
			const delta = e.delta || e.originalEvent.wheelDelta;
			const zoomOut = delta ? delta < 0 : e.originalEvent.deltaY > 0;

			// Pan has to be enabled for focal zoom to work
			const originalPan = this.$panzoom.panzoom('option', 'disablePan');
			this.$panzoom.panzoom('option', 'disablePan', false);

			context.$panzoom.panzoom('zoom', zoomOut, {
				increment: 0.1,
				animate: false,
				focal: e
			});

			this.$panzoom.panzoom('option', 'disablePan', originalPan);
		});

		this.refreshScale();
	}

	updateSchedule() {
		if (this.saving) return;

		if (this.is_repaired) {
			if (!this.repair_notes.trim()) {
				this.app.notifications.showWarning('Please enter repair details.');
				return;
			}

			if (this.data.light.function_test_status === -1 && !this.has_manual_function_datetime) {
				this.app.notifications.showDanger('You must re-schedule failed function test to register repair.');
				return;
			}

			if (this.data.light.duration_test_status === -1 && !this.has_manual_duration_datetime) {
				this.app.notifications.showDanger('You must re-schedule failed duration test to register repair.');
				return;
			}
		}
		if (this.has_manual_function_datetime && !this.manual_function_datetime) {
			this.app.notifications.showWarning('Please enter function test date/time.');
			return;
		}
		if (this.has_manual_duration_datetime && !this.manual_duration_datetime) {
			this.app.notifications.showWarning('Please enter duration test date/time.');
			return;
		}

		this.saving = true;
		this.api.emergency.saveLightSchedule({
			id: this.data.light.id,
			building_id: this.data.light.building_id,
			repair_notes: this.is_repaired ? this.repair_notes : null,
			manual_function_datetime: this.has_manual_function_datetime ? MySQLDateToISOPipe.dateToString(this.manual_function_datetime) : null,
			manual_duration_datetime: this.has_manual_duration_datetime ? MySQLDateToISOPipe.dateToString(this.manual_duration_datetime) : null
		}, () => {
			this.saving = false;
			this.modal.close();
			this.app.notifications.showSuccess('Emergency light schedule updated.');
		}, response => {
			this.saving = false;
			this.app.notifications.showDanger(response.message);
		});
	}

}
