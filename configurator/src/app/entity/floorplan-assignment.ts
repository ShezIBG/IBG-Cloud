import { EntityTypes } from './entity-types';
import { FloorPlan } from './floorplan';
import { Floor } from './floor';
import { Area } from './area';
import { Entity } from './entity';

export class FloorPlanAssignment extends Entity {

	static type = EntityTypes.FloorPlanAssignment;
	static groupName = 'Floor Plan Assignments';

	getTypeDescription() { return 'Floor Plan Assignment'; }
	getIconClass() { return 'md md-link'; }
	getParent() { return this.entityManager.get<FloorPlan>(EntityTypes.FloorPlan, this.data.floorplan_id); }
	getSort() { return [this.data.id]; }
	getTags() { return []; }

	getEntity() {
		if (this.data.floor_id) {
			return this.entityManager.get<Floor>(EntityTypes.Floor, this.data.floor_id);
		} else if (this.data.area_id) {
			return this.entityManager.get<Area>(EntityTypes.Area, this.data.area_id);
		}

		return null;
	}
}
