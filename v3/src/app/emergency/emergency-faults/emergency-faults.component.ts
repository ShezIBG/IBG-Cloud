import { EmergencyLightInfoModalComponent } from './../emergency-light-info-modal/emergency-light-info-modal.component';
import { AppService } from './../../app.service';
import { MySQLDateToISOPipe } from './../../shared/mysql-date-to-iso.pipe';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { Component, OnInit, OnDestroy, NgModuleRef } from '@angular/core';

@Component({
	selector: 'app-emergency-faults',
	templateUrl: './emergency-faults.component.html',
	styleUrls: ['./emergency-faults.component.less']
})
export class EmergencyFaultsComponent implements OnInit, OnDestroy {

	id: number;
	data: any = null;
	tab = 'active';
	filtered: any[] = [];
	search = '';

	private subs = [];

	constructor(
		private app: AppService,
		private api: ApiService,
		private route: ActivatedRoute,
		private moduleRef: NgModuleRef<any>
	) { }

	ngOnInit() {
		this.subs.push(this.route.params.subscribe(params => {
			this.id = params['id'];
			this.reloadData(() => {
				this.refreshFilteredList();
			});
		}));

		this.subs.push(this.route.queryParams.subscribe(params => {
			const tab = params['tab'];
			if (tab && ['active', 'resolved', 'repaired'].indexOf(tab) !== -1) this.tab = tab;
		}));

		this.subs.push(this.app.modal.modalService.modalClosed.subscribe(() => {
			this.reloadData(() => {
				this.refreshFilteredList();
			});
		}));
	}

	ngOnDestroy() {
		this.subs.forEach(sub => sub.unsubscribe());
	}

	reloadData(done = null, fail = null) {
		this.api.emergency.getBuildingFaults(this.id, res => {
			this.data = res.data;

			this.app.header.clearCrumbs();
			if (this.data && this.data.building_count > 1) this.app.header.addCrumb({ description: 'Buildings', route: '/emergency' });
			this.app.header.addCrumb({ description: this.data.building.description, route: '/emergency/building/' + this.data.building.id });
			this.app.header.addCrumb({ description: 'Faults', compact: true });

			const processFault = fault => {
				fault.fault_datetime = MySQLDateToISOPipe.stringToDate(fault.fault_datetime);
				fault.repair_datetime = MySQLDateToISOPipe.stringToDate(fault.repair_datetime);

				if (fault.function_test_failed && fault.duration_test_failed) {
					fault.test_failed = 'Both';
				} else if (fault.function_test_failed) {
					fault.test_failed = 'Function';
				} else if (fault.duration_test_failed) {
					fault.test_failed = 'Duration';
				} else {
					fault.test_failed = 'Unknown';
				}
			};

			this.data.active_faults.forEach(processFault);
			this.data.resolved_faults.forEach(processFault);
			this.data.repaired_faults.forEach(processFault);

			if (done) done();
		}, fail);
	}

	refreshFilteredList() {
		this.filtered = this.data[this.tab + '_faults'] || [];
	}

	getFlagIconClass(flag, errorIcon = 'md md-error') {
		return flag ? 'status-icon md md-check text-success' : 'status-icon ' + errorIcon + ' text-danger';
	}

	showLightDetails(light) {
		this.app.modal.open(EmergencyLightInfoModalComponent, this.moduleRef, light.id);
	}

	setTab(tab) {
		this.tab = tab;
		this.refreshFilteredList();
	}

}
