import { EntityTypes } from './entity-types';
import { FloorPlan } from './floorplan';
import { Entity } from './entity';

export class FloorPlanItem extends Entity {

	static type = EntityTypes.FloorPlanItem;
	static groupName = 'Floor Plan Items';

	get locked() { return !!this.data.locked; };
	set locked(value) { this.data.locked = value ? 1 : 0; };

	get isDirectional() { return this.data.direction !== null };
	set isDirectional(value) { this.data.direction = value ? 0 : null; };

	get direction() { return this.data.direction; }
	set direction(value) { this.data.direction = parseFloat(value) || 0; }

	getTypeDescription() { const e = this.getEntity(); return e ? e.getTypeDescription() : 'Floor Plan Item'; }
	getIconClass() { const e = this.getEntity(); return e ? e.getIconClass() : 'md md-grid-on'; }
	getParent() { return this.entityManager.get<FloorPlan>(EntityTypes.FloorPlan, this.data.floorplan_id); }
	getSort() { const e = this.getEntity(); return e ? [e.getTypeDescription(), e.getDescription()] : ['Floor Plan Item', this.data.id]; }
	getTags() { return []; }

	canDelete() { return true; }

	getAssignedTo(): Entity[] { return this.entityManager.find<Entity>(this.data.item_type, { id: this.data.item_id }); }

	getEntity(): Entity {
		return this.getAssignedTo()[0];
	}

}
