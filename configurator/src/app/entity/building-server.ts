import { Router } from './router';
import { Area } from './area';
import { EntityTypes } from './entity-types';
import { Entity, MovableEntity, ActivatableEntity } from './entity';

declare var Mangler: any;

export class BuildingServer extends Entity implements MovableEntity, ActivatableEntity {

	static type = EntityTypes.BuildingServer;
	static groupName = 'Building Servers';

	get isActive() { return this.data.active === 1; }
	set isActive(value) { this.data.active = value ? 1 : 0; }

	getTypeDescription() { return 'Building Server'; }
	getIconClass() { return 'ei ei-gateway'; }
	getParent() { return this.entityManager.get<Area>(EntityTypes.Area, this.data.area_id); }
	getSort() { return this.data.description; }
	getTags() { return ['equipment', 'assign-tree', 'assignables-tree']; }

	copyToArea(area: Area) {
		const data = Mangler.clone(this.data);
		data.id = area.entityManager.getAutoId();
		data.area_id = area.data.id;
		data.building_id = area.closest(EntityTypes.Building).data.id;
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
		const router = this.entityManager.get<Router>(EntityTypes.Router, this.data.router_id);
		return router ? [router] : [];
	}

	isUnassigned() {
		return !this.data.router_id;
	}

	isAssignableTo(entity: Entity) {
		return EntityTypes.isRouter(entity);
	}

	assignTo(entity: Entity) {
		if (EntityTypes.isRouter(entity)) {
			this.data.router_id = entity.data.id;
			this.data.remote_ip_address = entity.data.ip_address;
			this.refresh();
		}
	}

	unassignFrom(entity: Entity) {
		if (this.isAssignedTo(entity) && EntityTypes.isRouter(entity)) {
			this.data.router_id = null;
			this.data.ext_ip_address = null;
			this.refresh();
		}
	}

	getAreaDescription() {
		const area = this.closest(EntityTypes.Area);
		return area ? area.getDescription() : '';
	}

}
