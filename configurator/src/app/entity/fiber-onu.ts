import { FiberOLT } from './fiber-olt';
import { Area } from './area';
import { EntityTypes } from './entity-types';
import { Entity, MovableEntity } from './entity';

declare var Mangler: any;

export class FiberONU extends Entity implements MovableEntity {

	static type = EntityTypes.FiberONU;
	static groupName = 'Fibre ONUs';

	get onuNumber() { return this.data.onu; }
	set onuNumber(value) { this.data.onu = parseInt(value, 10) || 0; }

	getTypeDescription() { return 'Fibre ONU'; }
	getSubtitle() { return (this.data.port || '') + ' / ' + (this.data.onu || 0) + (this.data.serial_no ? ' - ' + this.data.serial_no : ''); }
	getIconClass() { return 'ei ei-router'; }
	getParent() { return this.entityManager.get<Area>(EntityTypes.Area, this.data.area_id); }
	getSort() { return [this.data.ont_id, this.data.port, this.data.onu]; }
	getTags() { return ['equipment', 'assignables-tree']; }

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
		const olt = this.entityManager.get<FiberOLT>(EntityTypes.FiberOLT, this.data.olt_id);
		return olt ? [olt] : [];
	}

	isUnassigned() {
		return !this.data.olt_id;
	}

	canAssignTo(entity: Entity) {
		let result = this.isAssignableTo(entity) && !this.getAssignedToType(entity);

		// Check if port and onu is set
		if (result && !this.data.port || this.data.onu === null) result = false;

		if (result) {
			// Make sure we don't have a device on the same port already
			const samePort = Mangler.find(entity.assigned, { entity: EntityTypes.FiberONU, port: this.data.port, onu: this.data.onu }).length;
			if (samePort) result = false;
		}
		return result;
	}

	isAssignableTo(entity: Entity) {
		return EntityTypes.isFiberOLT(entity);
	}

	assignTo(entity: Entity) {
		if (this.canAssignTo(entity)) {
			this.data.olt_id = entity.data.id;
			this.refresh();
		}
	}

	unassignFrom(entity: Entity) {
		if (this.isAssignedTo(entity) && EntityTypes.isFiberOLT(entity)) {
			this.data.olt_id = 0; // Not NULL
			this.refresh();
		}
	}

	getAreaDescription() {
		const area = this.closest(EntityTypes.Area);
		return area ? area.getDescription() : '';
	}

}
