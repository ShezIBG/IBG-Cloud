import { LightingAddScheduleModalComponent } from './../lighting-add-schedule-modal/lighting-add-schedule-modal.component';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy, NgModuleRef } from '@angular/core';
import { LightingTestScheduleModalComponent } from '../lighting-test-schedule-modal/lighting-test-schedule-modal.component';

declare var Mangler: any;

@Component({
	selector: 'app-lighting-schedules',
	templateUrl: './lighting-schedules.component.html'
})
export class LightingSchedulesComponent implements OnInit, OnDestroy {

	id: number;
	data: any = null;
	selectedSchedule = null;
	schedule = null;
	edit = false;
	dirty = false;

	days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
	copyDay = null;

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
		this.api.lighting.getBuildingSchedules(this.id, res => {
			this.data = res.data;

			this.app.header.clearCrumbs();
			if (this.data && this.data.building_count > 1) this.app.header.addCrumb({ description: 'Buildings', route: '/lighting' });
			this.app.header.addCrumb({ description: this.data.building.description, route: '/lighting/building/' + this.data.building.id });
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
		this.copyDay = null;
		this.edit = false;
		this.dirty = false;
		this.api.lighting.getSchedule(this.id, scheduleId, response => {
			this.schedule = response.data;
			this.schedule.record.off_on_holidays = !!this.schedule.record.off_on_holidays;
			this.schedule.items.forEach(item => {
				// Short format time
				item.time = (item.time + '').substr(0, 5);
				item.light_onoff = !!item.light_onoff;
			});
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	addSchedule() {
		const modalSub = this.app.modal.modalService.modalClosed.subscribe(() => {
			modalSub.unsubscribe();
			this.reloadData();
		});

		this.app.modal.open(LightingAddScheduleModalComponent, this.moduleRef, {
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
			minutes: 0,
			type: 'set-time',
			light_onoff: true
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
		this.api.lighting.saveSchedule(this.id, this.schedule, () => {
			this.app.notifications.showSuccess('Schedule saved.');
			this.reloadData();
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	deleteSchedule() {
		if (confirm('Are you sure you want to delete this schedule? Lights will be unassigned.')) {
			this.api.lighting.deleteSchedule(this.id, this.selectedSchedule, () => {
				this.app.notifications.showSuccess('Schedule deleted.');
				this.selectedSchedule = null;
				this.reloadData();
			}, response => {
				this.app.notifications.showDanger(response.message);
			});
		}
	}

	testLightGroup() {
		if (!this.selectSchedule) return;

		const modalSub = this.app.modal.modalService.modalClosed.subscribe(() => {
			modalSub.unsubscribe();
			this.reloadData();
		});

		this.app.modal.open(LightingTestScheduleModalComponent, this.moduleRef, {
			building_id: this.id,
			id: this.selectedSchedule
		});
	}

	copyScheduleItems(fromDay, toDay) {
		if (!fromDay || !toDay) return;

		this.dirty = true;
		const items = [];

		this.schedule.items.forEach(item => {
			if (item.day === fromDay) items.push(Mangler.clone(item));
		});

		this.schedule.items = this.schedule.items.filter(item => {
			return item.day !== toDay;
		});

		items.forEach(item => {
			item.day = toDay;
			this.schedule.items.push(item);
		});
	}

}
