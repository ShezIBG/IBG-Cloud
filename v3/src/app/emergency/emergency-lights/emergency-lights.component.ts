import { EmergencyLightInfoModalComponent } from './../emergency-light-info-modal/emergency-light-info-modal.component';
import { AppService } from './../../app.service';
import { MySQLDateToISOPipe } from './../../shared/mysql-date-to-iso.pipe';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { Component, OnInit, OnDestroy, ViewChild, NgModuleRef } from '@angular/core';

declare var $: any;
declare var Mangler: any;

@Component({
	selector: 'app-emergency-lights',
	templateUrl: './emergency-lights.component.html',
	styleUrls: ['./emergency-lights.component.less']
})
export class EmergencyLightsComponent implements OnInit, OnDestroy {

	@ViewChild('plan') plan;
	@ViewChild('zoomin') zoomin;
	@ViewChild('zoomout') zoomout;

	private subs = [];

	id: number;
	data: any = null;
	tab = 'all';
	filtered: any[] = [];

	passList: any[] = [];
	failList: any[] = [];
	warningList: any[] = [];

	selectedFloorPlan = null;
	$panzoom = null;
	scale = 1;
	touched = null;

	hovered = null;
	hoverTimer = null;

	search = '';

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
				this.selectFloorPlan(this.data.floorplans[0]);
			});
		}));

		this.subs.push(this.route.queryParams.subscribe(params => {
			const tab = params['tab'];
			if (tab && ['all', 'pass', 'fail', 'warning', 'floorplans'].indexOf(tab) !== -1) this.tab = tab;
		}));

		this.subs.push(this.app.modal.modalService.modalClosed.subscribe(() => {
			this.reloadData(() => {
				this.refreshFilteredList();

				// Preserve floorplan selection
				let fp = null;
				if (this.selectedFloorPlan) fp = Mangler.findOne(this.data.floorplans, { id: this.selectedFloorPlan.id });
				if (!fp) fp = this.data.floorplans[0];

				this.selectFloorPlan(fp);
			});
		}));
	}

	ngOnDestroy() {
		this.subs.forEach(sub => sub.unsubscribe());
	}

	reloadData(done = null, fail = null) {
		this.api.emergency.getBuildingLights(this.id, res => {
			this.data = res.data;

			this.app.header.clearCrumbs();
			if (this.data && this.data.building_count > 1) this.app.header.addCrumb({ description: 'Buildings', route: '/emergency' });
			this.app.header.addCrumb({ description: this.data.building.description, route: '/emergency/building/' + this.data.building.id });
			this.app.header.addCrumb({ description: 'Lights', compact: true });

			this.passList = [];
			this.failList = [];
			this.warningList = [];

			const floorPlanIndex = {};

			this.data.floorplans.forEach(fp => {
				fp.lights = [];
				floorPlanIndex[fp.id] = fp;
			});

			this.data.lights.forEach(light => {
				light.function_test_finished_datetime = MySQLDateToISOPipe.stringToDate(light.function_test_finished_datetime);
				light.duration_test_finished_datetime = MySQLDateToISOPipe.stringToDate(light.duration_test_finished_datetime);

				if (light.light_status === -1) {
					this.failList.push(light);
				} else if (light.light_status === 0) {
					this.warningList.push(light);
				} else {
					this.passList.push(light);
				}

				if (light.floorplan_id) {
					const fp = floorPlanIndex[light.floorplan_id];
					if (fp) fp.lights.push(light);
				}
			});

			if (done) done();
		}, fail);
	}

	refreshFilteredList() {
		switch (this.tab) {
			case 'pass': this.filtered = this.passList; break;
			case 'fail': this.filtered = this.failList; break;
			case 'warning': this.filtered = this.warningList; break;
			default: this.filtered = this.data.lights; break;
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

	showLightDetails(light) {
		this.app.modal.open(EmergencyLightInfoModalComponent, this.moduleRef, light.id);
	}

	setTab(tab) {
		this.tab = tab;
		this.refreshFilteredList();

		if (this.tab === 'floorplans' && this.selectedFloorPlan) {
			setTimeout(() => this.initPanzoom(), 0);
		}
	}

	selectFloorPlan(fp) {
		this.selectedFloorPlan = fp;
		if (fp) {
			setTimeout(() => this.initPanzoom(), 0);
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

	lightMouseEnter(light) {
		clearTimeout(this.hoverTimer);
		this.hovered = light;
	}

	lightMouseLeave() {
		clearTimeout(this.hoverTimer);
		this.hoverTimer = setTimeout(() => { this.hovered = null; }, 50);
	}

	lightTouchStart(light) {
		this.touched = light;
	}

	lightTouchEnd(light) {
		if (this.touched === light) {
			this.showLightDetails(light);
		}
		this.touched = null;
	}

	infoMouseEnter() {
		clearTimeout(this.hoverTimer);
	}

	infoMouseLeave() {
		clearTimeout(this.hoverTimer);
		this.hoverTimer = setTimeout(() => { this.hovered = null; }, 50);
	}

}
