import { EntityTypes } from './../entity-types';
import { AppService } from './../../app.service';
import { Area } from './../area';
import { Floor } from './../floor';
import { Entity, isMovableEntity } from './../entity';
import { Component, Input } from '@angular/core';

@Component({
	selector: 'entity-move',
	templateUrl: './entity-move.component.html'
})
export class EntityMoveComponent {

	@Input() entity: Entity;
	@Input() type = 'toolbar';

	isMovableEntity = isMovableEntity;

	constructor(private app: AppService) { }

	getFloors() {
		return this.entity.entityManager.find<Floor>(EntityTypes.Floor);
	}

	getAreas(f: Floor) {
		return f.items.filter(item => {
			return EntityTypes.isArea(item);
		});
	}

	moveToArea(area: Area) {
		if (isMovableEntity(this.entity)) {
			this.entity.moveToArea(area);
			this.entity.jumpTo(this.app);
		}
	}

}
