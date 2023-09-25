import { FloorplanComponent } from './../floorplan/floorplan.component';
import { EntitySortPipe } from './../entity/entity-sort.pipe';
import { Entity } from './../entity/entity';
import { Injectable, EventEmitter } from '@angular/core';

@Injectable()
export class ScreenService {

	type: string;
	filter: string;
	wideLayout = false;

	treeEntity: Entity = null;
	treeEntitySelected: EventEmitter<Entity> = new EventEmitter();

	detailEntity: Entity = null;
	detailEntitySelected: EventEmitter<Entity> = new EventEmitter();
	detailList = false;

	assignablesMode = 'all-assignable';
	assignables: Entity[] = [];

	floorPlanComponent: FloorplanComponent = null;

	showCTCategories = false;

	selectTreeEntity(entity: Entity) {
		this.treeEntity = entity;
		this.treeEntitySelected.emit(entity);
	}

	selectDetailEntity(entity: Entity) {
		this.detailEntity = entity;
		this.detailEntitySelected.emit(entity);
	}

	clearAssignables() {
		this.assignables = [];
	}

	isAssignableSelected(entity: Entity) {
		return this.assignables.indexOf(entity) !== -1;
	}

	addAssignable(entity: Entity) {
		if (!this.isAssignableSelected(entity)) {
			this.assignables.push(entity);
			this.assignables = EntitySortPipe.transform(this.assignables);
		}
	}

	removeAssignable(entity: Entity) {
		const i = this.assignables.indexOf(entity);
		if (i !== -1) this.assignables.splice(i, 1);
	}

	toggleAssignable(entity: Entity) {
		if (this.isAssignableSelected(entity)) {
			this.removeAssignable(entity);
		} else {
			this.addAssignable(entity);
		}
	}

}
