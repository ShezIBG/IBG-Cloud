import { ControlAddScheduleModalComponent } from './../control-add-schedule-modal/control-add-schedule-modal.component';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy, NgModuleRef } from '@angular/core';
import { KnxValue } from '../knx-value';

declare var Mangler: any;

@Component({
	selector: 'app-control-schedules',
	templateUrl: './control-schedules.component.html'
})
export class ControlSchedulesComponent implements OnInit, OnDestroy {

	id: number;
	data: any = null;
	selectedSchedule = null;
	schedule = null;
	groupedItems = [];
	slotIndex = {};
	edit = false;

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
		this.api.control.getBuildingSchedules(this.id, res => {
			this.data = res.data;

			this.data.item_types.forEach(t => {
				t.schedules = Mangler.find(this.data.schedules, { item_type_id: t.id }) || [];
			});

			this.app.header.clearCrumbs();
			if (this.data && this.data.building_count > 1) this.app.header.addCrumb({ description: 'Buildings', route: '/control' });
			this.app.header.addCrumb({ description: this.data.building.description, route: '/control/building/' + this.data.building.id });
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
		this.groupedItems = [];
		this.slotIndex = [];
		this.copyDay = null;
		this.edit = false;
		this.api.control.getSchedule(this.id, scheduleId, response => {
			this.schedule = response.data;
			this.schedule.record.off_on_holidays = !!this.schedule.record.off_on_holidays;

			// Build slot index for easy lookup
			this.slotIndex = {};
			this.schedule.slots.forEach(slot => {
				this.slotIndex[slot.id] = slot;
			});

			this.schedule.items.forEach(item => {
				// Short format time
				item.time = (item.time + '').substr(0, 5);
				item.end_time = item.end_time ? (item.end_time + '').substr(0, 5) : null;
				const slot = this.slotIndex[item.item_type_slot_id];
				item.knx = new KnxValue(0, slot ? slot.knx_datatype : null, slot ? slot.knx_subtype : null, item.baos_value, false);
			});

			// Group values together by day/time/end_time/type/minutes/repeat_minutes
			const groupIndex = {};
			this.groupedItems = [];
			this.schedule.items.forEach(item => {
				if (item.item_type_slot_id === null) {
					// Expire items are always on their own
					this.groupedItems.push({
						day: item.day,
						time: item.time,
						end_time: item.end_time,
						type: item.type,
						minutes: item.minutes,
						repeat_minutes: item.repeat_minutes,
						subitems: [item]
					});
					return;
				}

				let g = groupIndex['' + item.day + '/' + item.time + '/' + (item.end_time || '') + '/' + item.type + '/' + item.minutes + '/' + item.repeat_minutes];
				if (g) {
					g.subitems.push(item);
				} else {
					g = {
						day: item.day,
						time: item.time,
						end_time: item.end_time,
						type: item.type,
						minutes: item.minutes,
						repeat_minutes: item.repeat_minutes,
						subitems: [item]
					};

					this.groupedItems.push(g);
					groupIndex['' + item.day + '/' + item.time + '/' + (item.end_time || '') + '/' + item.type + '/' + item.minutes + '/' + item.repeat_minutes] = g;
				}
			});
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	addSchedule(typeId) {
		const modalSub = this.app.modal.modalService.modalClosed.subscribe(() => {
			modalSub.unsubscribe();
			this.reloadData();
		});

		this.app.modal.open(ControlAddScheduleModalComponent, this.moduleRef, {
			building_id: this.id,
			item_type_id: typeId
		});
	}

	getFilteredItems(day) {
		return this.groupedItems.filter(item => {
			return item.day === day;
		});
	}

	addItem(day) {
		this.groupedItems.push({
			day: day,
			time: '',
			end_time: null,
			type: 'set-time',
			repeat_minutes: 60,
			subitems: []
		});
	}

	addExpireItem(day) {
		this.groupedItems.push({
			day: day,
			time: '',
			end_time: null,
			type: 'set-time',
			repeat_minutes: 60,
			subitems: [
				{
					id: 'new',
					weekly_schedule_id: this.schedule.record.id,
					day: day,
					time: '',
					end_time: null,
					minutes: 0,
					repeat_minutes: 60,
					type: 'set-time',
					item_type_slot_id: null,
					baos_value: 'NULL'
				}
			]
		});
	}

	addSubitem(group, slotId) {
		const slot = this.slotIndex[slotId];
		const item: any = {
			id: 'new',
			weekly_schedule_id: this.schedule.record.id,
			day: group.day,
			time: group.time,
			end_time: group.end_time,
			minutes: 0,
			repeat_minutes: group.repeat_minutes,
			type: group.type,
			item_type_slot_id: slotId,
			baos_value: slot.knx_datatype === 1 ? 0 : 'NULL'
		};

		item.knx = new KnxValue(0, slot ? slot.knx_datatype : null, slot ? slot.knx_subtype : null, item.baos_value, false);
		group.subitems.push(item);
	}

	groupAddList(group) {
		if (group.subitems.length === 1 && group.subitems[0].item_type_slot_id === null) return [];

		const typeList = [];
		group.subitems.forEach(item => {
			typeList.push(item.item_type_slot_id);
		});

		return this.schedule.slots.filter(slot => {
			return typeList.indexOf(slot.id) === -1;
		});
	}

	deleteItem(group, item) {
		const i = group.subitems.indexOf(item);
		if (i !== -1) {
			group.subitems.splice(i, 1);
			group.subitems = group.subitems.slice();
		}

		if (!group.subitems.length) {
			// Delete empty group
			this.deleteGroup(group);
		}
	}

	deleteGroup(group) {
		const i = this.groupedItems.indexOf(group);
		if (i !== -1) {
			this.groupedItems.splice(i, 1);
			this.groupedItems = this.groupedItems.slice();
		}
	}

	saveSchedule() {
		const data = Mangler.clone(this.schedule);
		data.items = [];

		this.groupedItems.forEach(group => {
			group.subitems.forEach(item => {
				const newItem = Mangler.clone(item);
				newItem.baos_value = item.knx ? item.knx.value : 'NULL';
				delete newItem.knx;
				newItem.day = group.day;
				newItem.time = group.time;
				newItem.end_time = group.end_time;
				newItem.type = group.type;
				newItem.minutes = group.minutes;
				newItem.repeat_minutes = group.repeat_minutes;
				data.items.push(newItem);
			});
		});

		this.api.control.saveSchedule(this.id, data, () => {
			this.app.notifications.showSuccess('Schedule saved.');
			this.reloadData();
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	deleteSchedule() {
		if (confirm('Are you sure you want to delete this schedule? Lights will be unassigned.')) {
			this.api.control.deleteSchedule(this.id, this.selectedSchedule, () => {
				this.app.notifications.showSuccess('Schedule deleted.');
				this.selectedSchedule = null;
				this.reloadData();
			}, response => {
				this.app.notifications.showDanger(response.message);
			});
		}
	}

	copyScheduleItems(fromDay, toDay) {
		if (!fromDay || !toDay) return;

		const items = [];

		this.groupedItems.forEach(group => {
			if (group.day === fromDay) {
				const newGroup = Mangler.clone(group);
				group.subitems.forEach(item => {
					const slot = this.slotIndex[item.item_type_slot_id];
					item.knx = new KnxValue(0, slot ? slot.knx_datatype : null, slot ? slot.knx_subtype : null, item.baos_value, false);
				});
				items.push(newGroup);
			}
		});

		this.groupedItems = this.groupedItems.filter(item => {
			return item.day !== toDay;
		});

		items.forEach(group => {
			group.day = toDay;
			this.groupedItems.push(group);
		});
	}

}
