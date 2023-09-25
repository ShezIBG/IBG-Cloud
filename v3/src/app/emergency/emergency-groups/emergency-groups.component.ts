import { AppService } from './../../app.service';
import { MySQLDateToISOPipe } from './../../shared/mysql-date-to-iso.pipe';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { Component, OnInit, OnDestroy, ViewChild } from '@angular/core';

declare var $: any;
declare var Mangler: any;

@Component({
	selector: 'app-emergency-groups',
	templateUrl: './emergency-groups.component.html'
})
export class EmergencyGroupsComponent implements OnInit, OnDestroy {

	@ViewChild('plan') plan;
	@ViewChild('zoomin') zoomin;
	@ViewChild('zoomout') zoomout;

	id: number;
	data: any = null;
	groupIndex = {};
	floorPlanIndex = {};

	selectedGroup = null;
	selectedFloorPlan = null;

	editMode = false;
	function_test_datetime = null;
	duration_test_datetime = null;
	disableButtons = false;
	originalGroups = {};
	newGroup = {
		id: 'new',
		description: 'New',
		function_test_datetime: '',
		duration_test_datetime: ''
	};
	unassignedGroup = {
		id: null,
		description: 'Unassigned',
		unassigned: true
	};

	$panzoom = null;
	scale = 1;
	touched = null;

	hovered = null;
	hoverTimer = null;

	private sub: any;

	constructor(
		private app: AppService,
		private api: ApiService,
		private route: ActivatedRoute
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
		this.api.emergency.getBuildingGroups(this.id, res => {
			this.data = res.data;

			this.app.header.clearCrumbs();
			if (this.data && this.data.building_count > 1) this.app.header.addCrumb({ description: 'Buildings', route: '/emergency' });
			this.app.header.addCrumb({ description: this.data.building.description, route: '/emergency/building/' + this.data.building.id });
			this.app.header.addCrumb({ description: 'Groups', compact: true });

			const groupId = this.selectedGroup ? this.selectedGroup.id : null;
			const floorplanId = this.selectedFloorPlan ? this.selectedFloorPlan.id : null;

			this.processData();

			const group = (groupId ? this.groupIndex[groupId] : null) || this.data.groups[0];
			let fp = floorplanId !== null ? this.floorPlanIndex[floorplanId] : null;

			this.selectedFloorPlan = null;
			this.selectGroup(group);
			if (fp && this.selectedGroup.floorplans.indexOf(fp) === -1) fp = this.selectedGroup.floorplans[0];
			if (fp) this.selectFloorPlan(fp);

			if (done) done();
		}, fail);
	}

	processData() {
		this.groupIndex = {};
		this.data.groups.forEach(group => {
			group.floorplans = [];
			this.groupIndex[group.id] = group;
		});

		const unplacedFloorPlan: any = { id: 0, description: 'Unplaced', unplaced: true };

		this.floorPlanIndex = {};
		this.data.floorplans.push(unplacedFloorPlan);
		this.data.floorplans.forEach(fp => {
			fp.lights = [];
			this.floorPlanIndex[fp.id] = fp;
		});

		this.data.lights.forEach(light => {
			const fp = this.floorPlanIndex[light.floorplan_id || 0];
			if (fp) fp.lights.push(light);

			if (light.group_id) {
				const group = this.groupIndex[light.group_id];

				if (group && group.floorplans.indexOf(fp) === -1) {
					group.floorplans.push(fp);
				}
			}
		});

		this.data.groups.forEach(group => {
			group.floorplans.sort((a, b) => {
				return this.data.floorplans.indexOf(a) - this.data.floorplans.indexOf(b);
			})
		});

		if (unplacedFloorPlan.lights.length === 0) this.data.floorplans.pop();
	}

	selectGroup(group) {
		this.selectedGroup = group;
		if (!group) return;
		if (!this.selectedFloorPlan || group.floorplans.indexOf(this.selectedFloorPlan) === -1) this.selectFloorPlan(group.floorplans[0]);
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

	getLightCount(group, fp = null) {
		let count = 0;
		const lightsArray = fp === null ? this.data.lights : fp.lights;
		lightsArray.forEach(light => {
			if (light.group_id === group.id) count++;
		});
		return count;
	}

	lightClicked(light) {
		if (this.editMode) {
			// Add light to edited group

			if (light.group_id === this.selectedGroup.id) {
				light.group_id = this.originalGroups[light.id] || null;
			} else {
				this.originalGroups[light.id] = light.group_id;
				light.group_id = this.selectedGroup.id;
			}

		} else {
			// Select group associated with clicked light

			const group = this.groupIndex[light.group_id];
			if (!group) return;

			const fp = this.selectedFloorPlan;
			this.selectGroup(group);
		}
	}

	getLightGroup(light) {
		if (light.group_id === 'new') return this.newGroup;
		if (!light.group_id) return this.unassignedGroup;
		return this.groupIndex[light.group_id];
	}

	editGroup() {
		this.originalGroups = {};
		this.editMode = true;

		if (!this.selectedFloorPlan) this.selectFloorPlan(this.data.floorplans[0]);

		this.function_test_datetime = MySQLDateToISOPipe.stringToDate(this.selectedGroup.function_test_datetime);
		this.duration_test_datetime = MySQLDateToISOPipe.stringToDate(this.selectedGroup.duration_test_datetime);
	}

	addGroup() {
		this.selectedGroup = Mangler.clone(this.newGroup);
		this.selectedGroup.description = '';
		this.editGroup();
	}

	cancelEdit() {
		this.disableButtons = true;
		this.reloadData(() => {
			this.disableButtons = false;
			this.editMode = false;
		}, res => {
			this.app.notifications.showDanger(res.message || 'An error has occurred.');
			this.disableButtons = false;
			this.editMode = false;
		});
	}

	saveEdit() {
		this.selectedGroup.function_test_datetime = MySQLDateToISOPipe.dateToString(this.function_test_datetime);
		this.selectedGroup.duration_test_datetime = MySQLDateToISOPipe.dateToString(this.duration_test_datetime);

		this.disableButtons = true;

		const lights = Mangler(this.data.lights).find({ group_id: this.selectedGroup.id }).extract('id');

		this.api.emergency.saveBuildingGroup({
			'id': this.selectedGroup.id,
			'building_id': this.data.building.id,
			'description': this.selectedGroup.description,
			'function_test_datetime': this.selectedGroup.function_test_datetime,
			'duration_test_datetime': this.selectedGroup.duration_test_datetime,
			'lights': lights.items
		}, () => {
			this.cancelEdit();
			this.app.notifications.showSuccess(this.selectedGroup.id === 'new' ? 'Emergency light group created.' : 'Emergency light group updated.');
		}, res => {
			this.disableButtons = false;
			this.app.notifications.showDanger(res.message || 'An error has occurred.');
		});
	}

	deleteGroup() {
		if (confirm('Are you sure you want to delete this group?')) {
			this.api.emergency.deleteBuildingGroup({
				'id': this.selectedGroup.id,
				'building_id': this.data.building.id
			}, () => {
				this.cancelEdit();
				this.app.notifications.showSuccess('Emergency light group deleted.');
			}, res => {
				this.disableButtons = false;
				this.app.notifications.showDanger(res.message || 'An error has occurred.');
			});
		}
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
			this.lightClicked(light);
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
