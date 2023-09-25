import { EntityTypes } from './../entity-types';
import { Area } from './../area';
import { AppService } from './../../app.service';
import { ScreenService } from './../../screen/screen.service';
import { Entity } from './../entity';
import { Building } from './../building';
import { Component, Input, Output, EventEmitter, OnInit, OnChanges, OnDestroy } from '@angular/core';

declare var $: any;
declare var Mangler: any;

@Component({
	selector: 'app-assignables-tree',
	templateUrl: './assignables-tree.component.html'
})
export class AssignablesTreeComponent implements OnInit, OnChanges, OnDestroy {

	building: Building = null;
	@Input() parent: Entity;

	@Output() entitySelected: EventEmitter<Entity> = new EventEmitter();

	closedNodes = [];
	hover = null;

	subscriptions = [];

	constructor(public screen: ScreenService, public app: AppService) {
		this.building = app.building;
	}

	ngOnInit() {
		this.subscriptions.push(this.building.entityManager.onEntityDeletedEvent.subscribe((entity: Entity) => {
			this.screen.removeAssignable(entity);
		}));
	}

	ngOnChanges() {
		// Clear selection if entity changes
		this.screen.clearAssignables();

		// By default, collapse all areas apart from the one the entity is in (or if there were any assignments previously made from that area).
		const closed = Mangler(this.building.entityManager.find<Area>(EntityTypes.Area));
		closed.removeItem(this.parent.closest(EntityTypes.Area));
		this.parent.assigned.forEach((entity: Entity) => {
			closed.removeItem(entity.closest(EntityTypes.Area));
		});
		this.closedNodes = closed.items;
	}

	ngOnDestroy() {
		this.subscriptions.forEach(sub => sub.unsubscribe());
	}

	toggleNode(entity) {
		const index = this.closedNodes.indexOf(entity);
		if (index === -1) {
			this.closedNodes.push(entity);
		} else {
			this.closedNodes.splice(index, 1);
		}
	}

	isOpen(entity) {
		return this.closedNodes.indexOf(entity) === -1;
	}

	toggleAssignable(entity) {
		if (entity.canAssignTo(this.parent)) this.screen.toggleAssignable(entity);
	}

	isLeaf(entity: Entity) {
		switch (this.screen.assignablesMode) {
			case 'all-assignable':
				return !entity.hasAssignableItemsTo(this.parent);
			case 'unassigned':
				return !entity.hasAssignableItemsTo(this.parent, true);
			default:
				return !entity.hasUnassignedItems();
		}
	}

}
