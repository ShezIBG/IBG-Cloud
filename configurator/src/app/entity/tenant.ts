import { EntityTypes } from './entity-types';
import { Area } from './area';
import { Entity } from './entity';

export class Tenant extends Entity {

	static type = EntityTypes.Tenant;
	static groupName = 'Tenants';

	getTypeDescription() { return 'Current Tenant'; }
	getIconClass() { return 'md md-person'; }
	getParent() { return this.entityManager.get<Area>(EntityTypes.Area, this.data.area_id); }
	getSort() { return this.data.description; }
	getTags() { return []; }

	getAssignedTo() {
		const area = this.entityManager.get<Area>(EntityTypes.Area, this.data.area_id);
		return area ? [area] : [];
	}

	unassignFrom(entity: Entity) {
		alert('You cannot unassign a tenant via the Configurator. Please end the current lease via the Building Manager.');
	}

}
