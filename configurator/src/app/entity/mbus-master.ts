import { EntityTypes } from './entity-types';
import { Gateway } from './gateway';
import { Area } from './area';
import { Entity, MovableEntity, ActivatableEntity } from './entity';

declare var Mangler: any;

export class MBusMaster extends Entity implements MovableEntity, ActivatableEntity {

	static type = EntityTypes.MBusMaster;
	static groupName = 'M-Bus Masters';

	get isActive() { return this.data.active === 1; }
	set isActive(value) { this.data.active = value ? 1 : 0; }

	getTypeDescription() { return 'M-Bus Master'; }
	getIconClass() { return 'md md-directions-bus'; }
	getParent() { return this.entityManager.get<Area>(EntityTypes.Area, this.data.area_id); }
	getSort() { return this.data.description; }
	getTags() { return ['equipment', 'assign-tree', 'assignables-tree']; }

	hasBus(type: string): boolean { return type === Entity.BUS_TYPE_MBUS; }

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
		const gateway = this.entityManager.get<Gateway>(EntityTypes.Gateway, this.data.gateway_id);
		return gateway ? [gateway] : [];
	}

	isUnassigned() {
		return !this.data.gateway_id;
	}

	isAssignableTo(entity: Entity) {
		return EntityTypes.isGateway(entity) && entity.data.type === 'MB30';
	}

	assignTo(entity: Entity, options: any = {}) {
		if (EntityTypes.isGateway(entity) && entity.data.type === 'MB30') {
			this.data.gateway_id = entity.data.id;
			this.refresh();
		}
	}

	unassignFrom(entity: Entity) {
		if (this.isAssignedTo(entity) && EntityTypes.isGateway(entity)) {
			this.data.gateway_id = null;
			this.refresh();
		}
	}

}
