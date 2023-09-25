import { CoolHub } from './coolhub';
import { Area } from './area';
import { EntityTypes } from './entity-types';
import { Entity, MovableEntity, ActivatableEntity } from './entity';

declare var Mangler: any;

export class CoolPlug extends Entity implements MovableEntity, ActivatableEntity {

	static type = EntityTypes.CoolPlug;
	static groupName = 'CoolPlugs';

	get isActive() { return this.data.active === 1; }
	set isActive(value) { this.data.active = value ? 1 : 0; }

	get coolPlugId() { return this.data.coolplug_id; }
	set coolPlugId(value) { this.data.coolplug_id = parseInt(value, 10) || 0; }

	get minSetpoint() { return this.data.min_setpoint; }
	set minSetpoint(value) { this.data.min_setpoint = parseInt(value, 10) || null; }

	get maxSetpoint() { return this.data.max_setpoint; }
	set maxSetpoint(value) { this.data.max_setpoint = parseInt(value, 10) || null; }

	getTypeDescription() { return 'CoolPlug'; }
	getSubtitle() { return (this.data.line || '') + ' / ' + (this.data.coolplug_id || '0'); }
	getIconClass() { return 'md md-crop-square'; }
	getParent() { return this.entityManager.get<Area>(EntityTypes.Area, this.data.area_id); }
	getSort() { return [this.data.line, this.data.coolplug_id, this.data.description]; }
	getTags() { return ['equipment', 'assignables-tree', 'floorplan-item']; }

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
		const hub = this.entityManager.get<CoolHub>(EntityTypes.CoolHub, this.data.coolhub_id);
		return hub ? [hub] : [];
	}

	isUnassigned() {
		return !this.data.coolhub_id;
	}

	canAssignTo(entity: Entity) {
		let result = this.isAssignableTo(entity) && !this.getAssignedToType(entity);

		// Check if line and id is set
		if (result && !this.data.line || this.data.coolplug_id === null) result = false;

		if (result) {
			// Make sure we don't have a device on the same line/id already
			const sameId = Mangler.find(entity.assigned, { entity: EntityTypes.CoolPlug, line: this.data.line, coolplug_id: this.data.coolplug_id }).length;
			if (sameId) result = false;
		}
		return result;
	}

	isAssignableTo(entity: Entity) {
		return EntityTypes.isCoolHub(entity);
	}

	assignTo(entity: Entity) {
		if (this.canAssignTo(entity)) {
			this.data.coolhub_id = entity.data.id;
			this.refresh();
		}
	}

	unassignFrom(entity: Entity) {
		if (this.isAssignedTo(entity) && EntityTypes.isCoolHub(entity)) {
			this.data.coolhub_id = null;
			this.refresh();
		}
	}

	getAreaDescription() {
		const area = this.closest(EntityTypes.Area);
		return area ? area.getDescription() : '';
	}

}
