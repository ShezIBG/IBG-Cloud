import { Area } from './area';
import { EntityTypes } from './entity-types';
import { Entity, MovableEntity } from './entity';
import { Router } from './router';

export class SmoothPower extends Entity implements MovableEntity {

	static type = EntityTypes.SmoothPower;
	static groupName = 'SmoothPower Units';

	getTypeDescription() { return 'SmoothPower Unit'; }
	getDescription() { return 'SmoothPower'; }
	getSubtitle() { return this.data.serial; }
	getIconClass() { return 'eticon eticon-smooth-power'; }
	getParent() { return this.entityManager.get<Area>(EntityTypes.Area, this.data.area_id); }
	getSort() { return this.data.serial; }
	getTags() { return ['equipment', 'assignables-tree']; }

	canMove() { return true; }

	moveToArea(area: Area) {
		if (area.entityManager === this.entityManager) {
			this.data.area_id = area.data.id;
			this.data.building_id = area.entityManager.getBuilding().data.id;
			this.refresh();
			return true;
		}
		return false;
	}

	getAssignedTo() {
		const r = this.entityManager.get<Router>(EntityTypes.Router, this.data.router_id);
		return r ? [r] : [];
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
			this.refresh();
		}
	}

	unassignFrom(entity: Entity) {
		if (this.isAssignedTo(entity) && EntityTypes.isRouter(entity)) {
			this.data.router_id = null;
			this.refresh();
		}
	}

	getAreaDescription() {
		const area = this.closest(EntityTypes.Area);
		return area ? area.getDescription() : '';
	}

}
