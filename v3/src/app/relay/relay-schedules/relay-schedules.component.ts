import { RelayAddScheduleModalComponent } from './../relay-add-schedule-modal/relay-add-schedule-modal.component';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy, NgModuleRef } from '@angular/core';

@Component({
	selector: 'app-relay-schedules',
	templateUrl: './relay-schedules.component.html'
})
export class RelaySchedulesComponent implements OnInit, OnDestroy {

	id: number;
	data: any = null;
	selectedSchedule = null;
	schedule = null;
	edit = false;
	dirty = false;

	days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

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
		this.api.relay.getBuildingSchedules(this.id, res => {
			this.data = res.data;

			this.app.header.clearCrumbs();
			if (this.data && this.data.building_count > 1) this.app.header.addCrumb({ description: 'Buildings', route: '/relay' });
			this.app.header.addCrumb({ description: this.data.building.description, route: '/relay/building/' + this.data.building.id });
			this.app.header.addCrumb({ description: 'Weekly Schedules', compact: true });

			let selectId = this.selectedSchedule;
			if (!selectId && this.data.schedules && this.data.schedules.length) selectId = this.data.schedules[0].id;
			this.selectSchedule(selectId);

			if (done) done();
		}, fail);
	}

	selectSchedule(scheduleId) {
		// if (this.selectedSchedule === scheduleId) return;

		this.selectedSchedule = scheduleId;
		if (!scheduleId) return;

		this.schedule = null;
		this.edit = false;
		this.dirty = false;
		this.api.relay.getSchedule(scheduleId, response => {
			this.schedule = response.data;
			this.schedule.record.off_on_holidays = !!this.schedule.record.off_on_holidays;
			this.schedule.items.forEach(item => {
				// Short format time
				item.time = (item.time + '').substr(0, 5);
				item.new_state = !!item.new_state;
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

		this.app.modal.open(RelayAddScheduleModalComponent, this.moduleRef, {
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
			new_state: true
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
		this.api.relay.saveSchedule(this.schedule, () => {
			this.app.notifications.showSuccess('Schedule saved.');
			this.reloadData();
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	deleteSchedule() {
		if (confirm('Are you sure you want to delete this schedule? Devices will be unassigned.')) {
			this.api.relay.deleteSchedule(this.selectedSchedule, () => {
				this.app.notifications.showSuccess('Schedule deleted.');
				this.selectedSchedule = null;
				this.reloadData();
			}, response => {
				this.app.notifications.showDanger(response.message);
			});
		}
	}

}
