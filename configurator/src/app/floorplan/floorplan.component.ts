import { EntityTypes } from './../entity/entity-types';
import { FloorPlanAssignment } from './../entity/floorplan-assignment';
import { FloorPlanItem } from './../entity/floorplan-item';
import { AppService } from './../app.service';
import { FloorPlan } from './../entity/floorplan';
import { ScreenService } from './../screen/screen.service';
import { Entity } from './../entity/entity';
import { Component, OnInit, Input, OnChanges, OnDestroy, ViewChild } from '@angular/core';

declare var $: any;

@Component({
	selector: 'app-floorplan',
	templateUrl: './floorplan.component.html',
	styleUrls: ['./floorplan.component.css']
})
export class FloorplanComponent implements OnInit, OnDestroy, OnChanges {

	@Input() entity: Entity;
	@ViewChild('plan') plan;
	@ViewChild('fileInput') fileInput;

	subscriptions = [];
	selectedFloorPlan: FloorPlan = null;
	floorPlans: FloorPlan[] = [];

	$panzoom = null;
	scale = 1;

	newDescription = '';
	newDisabled = false;

	constructor(public app: AppService, public screen: ScreenService) { }

	ngOnInit() {
		this.subscriptions.push(this.entity.onItemAddedEvent.subscribe((item: Entity) => {
			if (EntityTypes.isFloorPlan(item)) this.refreshFloorPlans();
		}));

		this.subscriptions.push(this.entity.onItemRemovedEvent.subscribe((item: Entity) => {
			if (EntityTypes.isFloorPlan(item)) this.refreshFloorPlans();
		}));

		this.subscriptions.push(this.entity.entityManager.onEntityDeletedEvent.subscribe((entity: Entity) => {
			if (this.selectedFloorPlan === entity) this.selectedFloorPlan = this.floorPlans[0];
		}));

		this.screen.floorPlanComponent = this;
	}

	ngOnDestroy() {
		this.subscriptions.forEach(sub => sub.unsubscribe());
	}

	ngOnChanges() {
		this.refreshFloorPlans();
		this.selectFloorPlan(this.floorPlans[0]);
	}

	refreshFloorPlans() {
		let list = [];

		if (EntityTypes.isFloor(this.entity)) {
			list = this.entity.getFloorplans();
		} else if (EntityTypes.isArea(this.entity)) {
			list = this.entity.getFloorplans();
		}

		this.floorPlans = list;
	}

	selectFloorPlan(fp: FloorPlan) {
		this.selectedFloorPlan = fp;
		if (fp) {
			setTimeout(() => this.initPanzoom(), 0);
		}
	}

	canAddFloorPlan() {
		if (EntityTypes.isFloor(this.entity)) {
			return true;
		} else if (EntityTypes.isArea(this.entity)) {
			return !!this.entity.data.custom_floorplan;
		} else {
			return false;
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
			}
		});

		this.$panzoom.parent().off('mousewheel.focal').on('mousewheel.focal', (e) => {
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

	iconClicked(item: FloorPlanItem) {
		this.screen.selectDetailEntity(item.getEntity());
	}

	iconMouseEnter() {
		this.$panzoom.panzoom('option', 'disablePan', true);
	}

	iconMouseLeave() {
		this.$panzoom.panzoom('option', 'disablePan', false);
	}

	iconMouseDown(event, item: FloorPlanItem) {
		event.preventDefault();

		// Don't move if locked
		if (item.locked) return;

		const $container = this.$panzoom.find('.floorplan-container');

		const moveHandler = (e) => {
			const w = $container.width();
			const h = $container.height();
			const offset = $container.offset();

			let x = e.pageX - offset.left;
			let y = e.pageY - offset.top;

			const scale = this.$panzoom.panzoom('instance').scale;

			x = (x / w) * 100 * (1 / scale);
			y = (y / h) * 100 * (1 / scale);

			x = Math.min(Math.max(x, 0), 100);
			y = Math.min(Math.max(y, 0), 100);

			item.data.x = x;
			item.data.y = y;
		};

		const upHandler = (e) => {
			e.preventDefault();
			$container.off('mousemove', moveHandler);
		};

		$(document).one('mouseup', upHandler);
		$container.on('mousemove', moveHandler);
	}

	dragItemOnPlan(x, y, draggedEntity: Entity) {
		// Check if there is a valid floor plan selected
		if (!this.selectedFloorPlan) return;

		// Check if item is already placed somewhere
		let placed = false;
		draggedEntity.assigned.forEach((item: Entity) => {
			if (EntityTypes.isFloorPlanItem(item)) placed = true;
		});
		if (placed) return;

		// All good, carry on with dragging it onto the floor plan

		const helper = $('<div class="floorplan-icon bg-primary text-white"><i class="' + draggedEntity.getIconClass() + '"></i></div>').css({ top: y, left: x });

		let removeHandlers, moveItem, dropItem;

		removeHandlers = () => {
			$(document).off('mousemove', moveItem);
			$(document).off('mouseup', dropItem);
			helper.remove();
		};

		moveItem = (e) => {
			helper.css({ top: e.pageY, left: e.pageX });
		};

		dropItem = (e) => {
			removeHandlers();

			if (!this.selectedFloorPlan) return;

			let dropX = e.pageX;
			let dropY = e.pageY;

			const wrapper = this.$panzoom.closest('.floorplan-wrapper');
			const wrapperOffset = wrapper.offset();
			const wrapperWidth = wrapper.width();
			const wrapperHeight = wrapper.height();

			if (dropX < wrapperOffset.left || dropX > wrapperOffset.left + wrapperWidth) return;
			if (dropY < wrapperOffset.top || dropY > wrapperOffset.top + wrapperHeight) return;

			const container = this.$panzoom.find('.floorplan-container');
			const offset = container.offset();
			const width = container.width() * this.scale;
			const height = container.height() * this.scale;

			if (width === 0 || height === 0) return;

			dropX = ((dropX - offset.left) / width) * 100;
			dropY = ((dropY - offset.top) / height) * 100;

			if (dropX < 0 || dropX > 100 || dropY < 0 || dropY > 100) return;

			this.entity.entityManager.createEntity({
				entity: 'floorplan_item',
				floorplan_id: this.selectedFloorPlan.data.id,
				item_type: draggedEntity.type,
				item_id: draggedEntity.data.id,
				x: dropX,
				y: dropY,
				direction: null
			});
		};

		$(document).on('mousemove', moveItem);
		$(document).on('mouseup', dropItem);

		$('body').append(helper);
	}

	createFloorPlan() {
		if (!this.newDescription) {
			alert('Please enter description.');
			return;
		}

		this.newDisabled = true;

		this.app.uploadUserContent(this.fileInput, (uc) => {
			const newFloorPlan = this.entity.entityManager.createEntity({
				entity: 'floorplan',
				building_id: this.app.building.data.id,
				description: this.newDescription,
				image_id: uc.data.id
			});

			this.entity.entityManager.createEntity({
				entity: 'floorplan_assignment',
				floorplan_id: newFloorPlan.data.id,
				floor_id: EntityTypes.isFloor(this.entity) ? this.entity.data.id : null,
				area_id: EntityTypes.isArea(this.entity) ? this.entity.data.id : null
			});

			this.refreshFloorPlans();
			this.selectFloorPlan(newFloorPlan as FloorPlan);

			this.newDescription = '';
			this.newDisabled = false;
		}, (error) => {
			this.newDisabled = false;
			alert(error);
		});
	}

	getFloorPlanAssignment(fp: FloorPlan) {
		if (EntityTypes.isFloor(this.entity)) {
			return this.entity.entityManager.findOne<FloorPlanAssignment>(EntityTypes.FloorPlanAssignment, { floorplan_id: fp.data.id, floor_id: this.entity.data.id });
		} else if (EntityTypes.isArea(this.entity)) {
			return this.entity.entityManager.findOne<FloorPlanAssignment>(EntityTypes.FloorPlanAssignment, { floorplan_id: fp.data.id, area_id: this.entity.data.id });
		}
		return null;
	}

	assignFloorPlan(fp: FloorPlan) {
		if (fp && !this.getFloorPlanAssignment(fp)) {
			this.entity.entityManager.createEntity({
				entity: 'floorplan_assignment',
				floorplan_id: fp.data.id,
				floor_id: EntityTypes.isFloor(this.entity) ? this.entity.data.id : null,
				area_id: EntityTypes.isArea(this.entity) ? this.entity.data.id : null
			});
			this.refreshFloorPlans();
		}
	}

	unassignFloorPlan(fp: FloorPlan) {
		if (fp) {
			const assignment = this.getFloorPlanAssignment(fp);
			if (assignment) assignment.delete();
		}
		this.refreshFloorPlans();
	}
}
