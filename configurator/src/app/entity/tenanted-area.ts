import { EntityTypes } from './entity-types';
import { Area } from './area';
import { Entity } from './entity';

export class TenantedArea extends Entity {

	static type = EntityTypes.TenantedArea;
	static groupName = 'Tenanted Areas';

	getTypeDescription() { return 'Tenanted Area'; }
	getIconClass() { return 'md md-person-outline'; }
	getParent() { return this.entityManager.get<Area>(EntityTypes.Area, this.data.area_id); }
	getSort() { return this.data.area_id; }
	getTags() { return []; }

	getDescription() {
		const area = this.entityManager.get<Area>(EntityTypes.Area, this.data.area_id);
		return area ? area.getDescription() : '';
	}

}
