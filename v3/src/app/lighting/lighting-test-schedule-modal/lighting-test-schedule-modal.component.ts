import { ApiService } from './../../api.service';
import { Component, ViewChild } from '@angular/core';
import { ModalComponent, ModalEvent } from 'app/shared/modal/modal.component';
import { ModalService } from 'app/shared/modal/modal.service';

@Component({
	selector: 'app-lighting-test-schedule-modal',
	templateUrl: './lighting-test-schedule-modal.component.html'
})
export class LightingTestScheduleModalComponent {

	@ViewChild(ModalComponent) modal: ModalComponent;
	Math = Math;

	buildingId = null;
	id = null;
	data: any;

	state = false;
	switchError = '';
	syncError = '';
	isSynced = false;
	running = false;
	secondsLeft = 0;
	secondInterval = null;
	testTimer = null;
	syncTimer = null;

	destroyed = false;

	get currentState() { return this.state; }
	set currentState(value) {
		this.state = !!value;
		this.api.lighting.changeGroupState(this.buildingId, this.id, this.state, response => {
			if (this.destroyed) return;
			this.switchError = '';
		}, () => {
			if (this.destroyed) return;
			this.switchError = 'An error has occurred while trying to update light group.';
		});
	}

	constructor(
		private api: ApiService,
		private modalService: ModalService
	) {
		// Read data
		this.buildingId = this.modalService.data.building_id;
		this.id = this.modalService.data.id;
		this.api.lighting.getSchedule(this.buildingId, this.id, data => {
			this.data = data.data;
		});

		this.scheduleSyncCheck(true);
	}

	modalHandler(event: ModalEvent) {
		if (this.running) this.stopTest();
		clearTimeout(this.syncTimer);
		event.modal.close();
		this.destroyed = true;
	}

	startTest() {
		this.running = true;
		this.secondsLeft = 10 * 60;

		this.secondInterval = setInterval(() => {
			this.secondsLeft -= 1;
			if (this.secondsLeft <= 0) {
				this.secondsLeft = 0;
				this.stopTest();
			}
		}, 1000);

		this.scheduleToggle(true);
	}

	stopTest() {
		this.running = false;
		clearInterval(this.secondInterval);
		clearTimeout(this.testTimer);
		this.currentState = false;
	}

	scheduleSyncCheck(now = false) {
		this.syncTimer = setTimeout(() => {
			this.api.lighting.isScheduleSynced(this.buildingId, response => {
				if (this.destroyed) return;
				this.isSynced = response.data;
				this.syncError = '';
				this.scheduleSyncCheck();
			}, response => {
				if (this.destroyed) return;
				this.syncError = response.message;
				this.isSynced = false;
				this.scheduleSyncCheck();
			});
		}, now ? 0 : 10000);
	}

	scheduleToggle(now = false) {
		this.syncTimer = setTimeout(() => {
			this.currentState = !this.currentState;
			this.scheduleToggle();
		}, now ? 0 : 15000);
	}

}
