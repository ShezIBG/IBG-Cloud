import { FiberHeadendServer } from './fiber-headend-server';
import { EntityTypes } from './entity-types';
import { Area } from './area';
import { Entity, MovableEntity } from './entity';

declare var Mangler: any;

export class Router extends Entity implements MovableEntity {

	static type = EntityTypes.Router;
	static groupName = 'Routers';

	get ip_address() { return this.data.ip_address; }
	set ip_address(value) {
		this.data.ip_address = value;

		// Copy IP address to every headend server attached to the router
		const hes = this.entityManager.find<FiberHeadendServer>(EntityTypes.FiberHeadendServer, { router_id: this.data.id });
		hes.forEach(entity => entity.data.ext_ip_address = value);
	}

	getTypeDescription() { return 'Router'; }
	getIconClass() { return 'ei ei-router'; }
	getParent() { return this.entityManager.get<Area>(EntityTypes.Area, this.data.area_id); }
	getSort() { return this.data.description; }
	getTags() { return ['equipment', 'assign-tree', 'assignables-tree']; }

	copyToArea(area: Area) {
		const data = Mangler.clone(this.data);
		data.id = area.entityManager.getAutoId();
		data.area_id = area.data.id;
		return area.createEntity(data);
	}

	canMove() { return true; }

	moveToArea(area: Area) {
		if (area.entityManager === this.entityManager) {
			this.data.area_id = area.data.id;
			this.refresh();
			return true;
		}
		return false;
	}

	getAssignedTo() {
		const area = this.entityManager.get<Area>(EntityTypes.Area, this.data.area_id);
		return area ? [area] : [];
	}

}
