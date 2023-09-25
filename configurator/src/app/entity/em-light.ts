import { EntityTypes } from './entity-types';
import { Gateway } from './gateway';
import { EmLightType } from './em-light-type';
import { Area } from './area';
import { Entity, MovableEntity } from './entity';

declare var Mangler: any;

export class EmLight extends Entity implements MovableEntity {

	static type = EntityTypes.EmLight;
	static groupName = 'Emergency Lights';

	get isMaintained() { return !!this.data.is_maintained; }
	set isMaintained(value) { this.data.is_maintained = value ? 1 : 0; }

	getTypeDescription() { return 'Emergency Light'; }
	getSubtitle() { return this.data.zone_number; }
	getIconClass() { const type = this.getLightType(); return type ? type.data.icon : 'ei ei-control'; }
	getParent() { return this.data.area_id ? this.entityManager.get<Area>(EntityTypes.Area, this.data.area_id) : null; }
	getSort() { return [this.data.description]; }
	getTags() { return ['equipment', 'assignables-tree', 'floorplan-item']; }

	hasBus(type: string) { return type === Entity.BUS_TYPE_DALI; }
	getBusID(type: string): any { return type === Entity.BUS_TYPE_DALI ? this.data.dali_address : null; }

	copyToArea(area: Area) {
		const data = Mangler.clone(this.data);
		data.id = area.entityManager.getAutoId();
		data.area_id = area.data.id;
		return area.createEntity(data);
	}

	canMove() {
		let result = true;
		this.assigned.forEach(entity => {
			if (EntityTypes.isFloorPlanItem(entity)) result = false;
		});
		return result;
	}

	moveToArea(area: Area) {
		if (area.entityManager === this.entityManager) {
			this.data.area_id = area.data.id;
			this.refresh();
			return true;
		}
		return false;
	}

	getAssignedTo() {
		const gateway = this.data.gateway_id ? this.entityManager.get<Gateway>(EntityTypes.Gateway, this.data.gateway_id) : null;
		return gateway ? [gateway] : [];
	}

	getAssignedToInfo(entity: Entity) {
		if (!this.isAssignedTo(entity)) return '';
		return EntityTypes.isGateway(entity) ? entity.getDescription() + ', DALI address ' + this.data.dali_address : entity.getDescription();
	}

	isUnassigned() {
		return !this.data.gateway_id;
	}

	isAssignableTo(entity: Entity) {
		return EntityTypes.isGateway(entity) && entity.data.type === 'DLC64';
	}

	assignTo(entity: Entity, options: any = {}) {
		if (EntityTypes.isGateway(entity) && entity.data.type === 'DLC64' && (options.dali_address === 0 || options.dali_address)) {
			this.data.gateway_id = entity.data.id;
			this.data.dali_address = options.dali_address;
			this.refresh();
		}
	}

	unassignFrom(entity: Entity) {
		if (this.isAssignedTo(entity) && EntityTypes.isGateway(entity)) {
			this.data.gateway_id = null;
			this.data.dali_address = null;
			this.refresh();
		}
	}

	canClone() { return true; }
	getCloneDescription() { return this.data.description; }

	clone(description: string, options: any) {
		const data = Mangler.clone(this.data);
		data.id = this.entityManager.getAutoId();
		data.description = description;
		data.gateway_id = null;
		data.dali_address = null;

		return this.entityManager.createEntity(data);
	};

	getLightType() {
		return this.data.type_id ? this.entityManager.get<EmLightType>(EntityTypes.EmLightType, this.data.type_id) : null;
	}

	getLightTypeDescription() {
		const type = this.getLightType();
		return type ? type.getDescription() : '';
	}

}
