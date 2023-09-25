import { FiberHeadendServer } from './fiber-headend-server';
import { Area } from './area';
import { EntityTypes } from './entity-types';
import { Entity, MovableEntity, ActivatableEntity } from './entity';

declare var Mangler: any;

export class FiberOLT extends Entity implements MovableEntity, ActivatableEntity {

	static type = EntityTypes.FiberOLT;
	static groupName = 'Fibre OLTs';

	get isActive() { return this.data.active === 1; }
	set isActive(value) { this.data.active = value ? 1 : 0; }

	getTypeDescription() { return 'Fibre OLT'; }
	getSubtitle() { return this.data.serial_number; }
	getIconClass() { return 'md md-flare'; }
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
		const hes = this.entityManager.get<FiberHeadendServer>(EntityTypes.FiberHeadendServer, this.data.hes_id);
		return hes ? [hes] : [];
	}

	isUnassigned() {
		return !this.data.hes_id;
	}

	isAssignableTo(entity: Entity) {
		return EntityTypes.isFiberHeadendServer(entity);
	}

	assignTo(entity: Entity) {
		if (EntityTypes.isFiberHeadendServer(entity)) {
			this.data.hes_id = entity.data.id;
			this.refresh();
		}
	}

	unassignFrom(entity: Entity) {
		if (this.isAssignedTo(entity) && EntityTypes.isFiberHeadendServer(entity)) {
			this.data.hes_id = 0; // Not NULL
			this.refresh();
		}
	}

	getAreaDescription() {
		const area = this.closest(EntityTypes.Area);
		return area ? area.getDescription() : '';
	}

}
