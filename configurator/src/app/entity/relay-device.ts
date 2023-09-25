import { BuildingServer } from './building-server';
import { Area } from './area';
import { EntityTypes } from './entity-types';
import { Entity, MovableEntity, ActivatableEntity } from './entity';

declare var Mangler: any;

export class RelayDevice extends Entity implements MovableEntity, ActivatableEntity {

	static type = EntityTypes.RelayDevice;
	static groupName = 'Relay Devices';

	get isActive() { return this.data.active === 1; }
	set isActive(value) { this.data.active = value ? 1 : 0; }

	getTypeDescription() { return 'Relay Device'; }
	getSubtitle() { return this.data.serial_number; }
	getIconClass() { return 'md md-settings-input-composite'; }
	getParent() { return this.entityManager.get<Area>(EntityTypes.Area, this.data.area_id); }
	getSort() { return this.data.description; }
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
		const bs = this.entityManager.get<BuildingServer>(EntityTypes.BuildingServer, this.data.building_server_id);
		return bs ? [bs] : [];
	}

	isUnassigned() {
		return !this.data.building_server_id;
	}

	isAssignableTo(entity: Entity) {
		return EntityTypes.isBuildingServer(entity);
	}

	assignTo(entity: Entity) {
		if (EntityTypes.isBuildingServer(entity)) {
			this.data.building_server_id = entity.data.id;
			this.refresh();
		}
	}

	unassignFrom(entity: Entity) {
		if (this.isAssignedTo(entity) && EntityTypes.isBuildingServer(entity)) {
			this.data.building_server_id = null;
			this.refresh();
		}
	}

	getAreaDescription() {
		const area = this.closest(EntityTypes.Area);
		return area ? area.getDescription() : '';
	}

}
