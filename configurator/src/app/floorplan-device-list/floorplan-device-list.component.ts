import { EntityTypes } from './../entity/entity-types';
import { Area } from './../entity/area';
import { ScreenService } from './../screen/screen.service';
import { Entity } from './../entity/entity';
import { Component, OnInit, Input, OnDestroy, OnChanges } from '@angular/core';

declare var $: any;

@Component({
	selector: 'app-floorplan-device-list',
	templateUrl: './floorplan-device-list.component.html',
	styleUrls: ['./floorplan-device-list.component.css']
})
export class FloorplanDeviceListComponent implements OnInit, OnChanges, OnDestroy {

	@Input() entity;

	areas: Area[] = [];

	entitySubs = [];
	subs = [];

	constructor(public screen: ScreenService) { }

	ngOnInit() {
		this.subs.push(this.entity.entityManager.onEntityDeletedEvent.subscribe((entity: Entity) => {
			if (this.screen.detailEntity === entity) this.screen.selectDetailEntity(null);
		}));

		this.subscribeEntity();
	}

	ngOnChanges() {
		this.unsubscribeEntity();
		this.refreshAreas();
		this.subscribeEntity();
	}

	ngOnDestroy() {
		this.subs.forEach(sub => sub.unsubscribe());
		this.unsubscribeEntity();
	}

	refreshAreas() {
		if (EntityTypes.isArea(this.entity)) {
			this.areas = [this.entity];
		} else if (EntityTypes.isFloor(this.entity)) {
			this.areas = [];
			this.entity.items.forEach((area: Area) => {
				if (EntityTypes.isArea(area)) this.areas.push(area);
			});
		}
	}

	subscribeEntity() {
		if (EntityTypes.isFloor(this.entity)) {
			this.entitySubs.push(this.entity.onItemAddedEvent.subscribe((item: Entity) => {
				if (EntityTypes.isArea(item)) this.refreshAreas();
			}));

			this.entitySubs.push(this.entity.onItemRemovedEvent.subscribe((item: Entity) => {
				if (EntityTypes.isArea(item)) this.refreshAreas();
			}));
		}
	}

	unsubscribeEntity() {
		this.entitySubs.forEach(sub => sub.unsubscribe());
		this.entitySubs = [];
	}

	itemMouseDown(event, item: Entity) {
		event.preventDefault();

		const x = event.pageX;
		const y = event.pageY;

		let moveHandler, removeHandlers;

		removeHandlers = () => {
			$(document).off('mousemove', moveHandler);
			$(document).off('mouseup', removeHandlers);
		};

		moveHandler = (e) => {
			if (Math.abs(x - e.pageX) > 10 || Math.abs(y - e.pageY) > 10) {
				removeHandlers();
				if (this.screen.floorPlanComponent) {
					$(event.target).trigger('click');
					this.screen.floorPlanComponent.dragItemOnPlan(x, y, item);
				}
			}
		};

		$(document).on('mousemove', moveHandler);
		$(document).on('mouseup', removeHandlers);
	}

	isPlaced(entity: Entity) {
		let placed = false;
		entity.assigned.forEach((item: Entity) => {
			if (EntityTypes.isFloorPlanItem(item)) placed = true;
		});
		return placed;
	}

}
