import { EntityTypes } from './entity-types';
import { UserContent } from './user-content';
import { Entity } from './entity';

export class FloorPlan extends Entity {

	static type = EntityTypes.FloorPlan;
	static groupName = 'Floor Plans';

	getTypeDescription() { return 'Floor Plan'; }
	getIconClass() { return 'md md-grid-on'; }
	getParent() { return null; }
	getSort() { return [this.data.description]; }
	getTags() { return []; }

	getImageURL() {
		const uc = this.entityManager.get<UserContent>(EntityTypes.UserContent, this.data.image_id);
		return uc ? uc.getURL() : '';
	}

	getAssignments() {
		const result = [];

		this.items.forEach((entity: Entity) => {
			if (EntityTypes.isFloorPlanAssignment(entity)) result.push(entity);
		});

		return result;
	}

}
