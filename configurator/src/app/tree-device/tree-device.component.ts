import { EntityTypes } from './../entity/entity-types';
import { ScreenService } from './../screen/screen.service';
import { Entity } from './../entity/entity';
import { Building } from './../entity/building';
import { Component, Input, Output, EventEmitter, OnInit, OnDestroy } from '@angular/core';

declare var $: any;

@Component({
	selector: 'app-tree-device',
	templateUrl: './tree-device.component.html'
})
export class TreeDeviceComponent implements OnInit, OnDestroy {

	@Input() building: Building;

	@Output() entitySelected: EventEmitter<Entity> = new EventEmitter();

	closedNodes = [];
	hover = null;

	subscriptions = [];

	constructor(public screen: ScreenService) { }

	ngOnInit() {
		this.subscriptions.push(this.building.entityManager.onEntityAddedEvent.subscribe((entity: Entity) => {
			if (entity.hasAnyTag(['structure-tree', 'equipment-tree'])) this.screen.selectTreeEntity(entity);
		}));

		this.subscriptions.push(this.building.entityManager.onEntityDeletedEvent.subscribe((entity: Entity) => {
			if (this.screen.treeEntity === entity) this.screen.selectTreeEntity(null);
		}));

		this.subscriptions.push(this.screen.treeEntitySelected.subscribe((entity: Entity) => {
			if (entity) entity.scrollIntoView();
		}));

		if (this.building && this.building.items.length) {
			this.screen.selectTreeEntity(this.building.items[0]);
		}
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

	addFloor() {
		this.building.entityManager.createEntity({
			entity: 'floor',
			id: this.building.entityManager.getAutoId(),
			building_id: this.building.data.id,
			description: 'New block',
			display_order: this.building.entityManager.getAutoDisplayOrder()
		});
	}

	addArea() {
		const floor = this.screen.treeEntity.closest(EntityTypes.Floor);
		this.building.entityManager.createEntity({
			entity: 'area',
			id: this.building.entityManager.getAutoId(),
			floor_id: floor.data.id,
			description: 'New area',
			has_tenant_meter: 0,
			is_tenanted: 0,
			is_owner_occupied: 0,
			display_order: this.building.entityManager.getAutoDisplayOrder(),
			custom_floorplan: 0
		});
	}

}
