import { EntityTypes } from './../entity-types';
import { EntityAssignmentsPipe } from './../entity-assignments.pipe';
import { Entity } from './../entity';
import { Component, Input } from '@angular/core';

@Component({
	selector: 'entity-assignments',
	templateUrl: './entity-assignments.component.html'
})
export class EntityAssignmentsComponent {

	@Input() entity: Entity;
	@Input() parents = true;
	@Input() children = true;

	hovered;

	getTotal() {
		if (!this.entity) return 0;
		return EntityAssignmentsPipe.transform(this.entity.getAssignedTo().concat(this.entity.assigned), this.entity).length;
	}

	getAreaDescription(e: Entity) {
		const area = e.closest(EntityTypes.Area);
		return area ? area.getDescription() : 'None';
	}

}
