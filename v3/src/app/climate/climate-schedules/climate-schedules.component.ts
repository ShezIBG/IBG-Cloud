import { ClimateAddScheduleModalComponent } from './../climate-add-schedule-modal/climate-add-schedule-modal.component';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy, NgModuleRef } from '@angular/core';

@Component({
	selector: 'app-climate-schedules',
	templateUrl: './climate-schedules.component.html',
	styleUrls: ['./climate-schedules.component.less']
})
export class ClimateSchedulesComponent implements OnInit, OnDestroy {

	id: number;
	data: any = null;
	selectedSchedule = null;
	schedule = null;
	edit = false;
	dirty = false;

	days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

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
		{ id: '30', desc: '30' },
		{ id: '45', desc: '45' },
		{ id: '60', desc: '60' },
		{ id: 'horizontal', desc: 'Horizontal' },
		{ id: 'auto', desc: 'Auto' },
		{ id: 'off', desc: 'Off' }
	];

	private sub: any;

	constructor(
		private app: AppService,
		private api: ApiService,
		private route: ActivatedRoute,
		private moduleRef: NgModuleRef<any>
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			this.id = params['id'];
			this.reloadData();
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

	reloadData(done = null, fail = null) {
		this.api.climate.getBuildingSchedules(this.id, res => {
			this.data = res.data;

			this.app.header.clearCrumbs();
			if (this.data && this.data.building_count > 1) this.app.header.addCrumb({ description: 'Buildings', route: '/climate' });
			this.app.header.addCrumb({ description: this.data.building.description, route: '/climate/building/' + this.data.building.id });
			this.app.header.addCrumb({ description: 'Weekly Schedules', compact: true });

			let selectId = this.selectedSchedule;
			if (!selectId && this.data.schedules && this.data.schedules.length) selectId = this.data.schedules[0].id;
			this.selectSchedule(selectId);

			if (done) done();
		}, fail);
	}

	selectSchedule(scheduleId) {
		this.selectedSchedule = scheduleId;
		if (!scheduleId) return;

		this.schedule = null;
		this.edit = false;
		this.dirty = false;
		this.api.climate.getSchedule(scheduleId, response => {
			this.schedule = response.data;
			this.schedule.record.off_on_holidays = !!this.schedule.record.off_on_holidays;
			this.schedule.items.forEach(item => {
				// Short format time
				item.time = (item.time + '').substr(0, 5);
				item.ac_onoff = !!item.ac_onoff;
			});
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	addSchedule() {
		const modalSub = this.app.modal.modalService.modalClosed.subscribe(modalData => {
			modalSub.unsubscribe();
			this.reloadData();
		});

		this.app.modal.open(ClimateAddScheduleModalComponent, this.moduleRef, {
			building_id: this.id
		});
	}

	getFilteredItems(day) {
		return this.schedule.items.filter(item => {
			return item.day === day;
		});
	}

	addItem(day) {
		this.dirty = true;
		this.schedule.items.push({
			id: 'new',
			weekly_schedule_id: this.schedule.record.id,
			day: day,
			time: '',
			ac_setpoint: null,
			ac_onoff: true,
			ac_mode: null,
			ac_fanspeed: null,
			ac_swing: null
		});
	}

	deleteItem(item) {
		const i = this.schedule.items.indexOf(item);
		if (i !== -1) {
			this.dirty = true;
			this.schedule.items.splice(i, 1);
			this.schedule.item = this.schedule.items.slice();
		}
	}

	saveSchedule() {
		this.api.climate.saveSchedule(this.schedule, () => {
			this.app.notifications.showSuccess('Schedule saved.');
			this.reloadData();
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	deleteSchedule() {
		if (confirm('Are you sure you want to delete this schedule? Devices will be unassigned.')) {
			this.api.climate.deleteSchedule(this.selectedSchedule, () => {
				this.app.notifications.showSuccess('Schedule deleted.');
				this.selectedSchedule = null;
				this.reloadData();
			}, response => {
				this.app.notifications.showDanger(response.message);
			});
		}
	}

	isScheduleOutOfRange(s) {
		if (s.device_min_setpoint && s.schedule_min_setpoint && s.schedule_min_setpoint < s.device_min_setpoint) return true;
		if (s.device_max_setpoint && s.schedule_max_setpoint && s.schedule_max_setpoint > s.device_max_setpoint) return true;
		return false;
	}

}
