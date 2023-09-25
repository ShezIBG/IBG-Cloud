import { BuildingServer } from './building-server';
import { Area } from './area';
import { EntityTypes } from './entity-types';
import { Entity, MovableEntity, ActivatableEntity } from './entity';
import { RelayDevice } from './relay-device';

export class RelayPin extends Entity implements ActivatableEntity {

	static type = EntityTypes.RelayPin;
	static groupName = 'Relay Pins';

	get isActive() { return this.data.active === 1; }
	set isActive(value) { this.data.active = value ? 1 : 0; }

	get port() { return this.data.port; }
	set port(value) { this.data.port = parseInt(value, 10) || 0; }

	getTypeDescription() { return 'Relay Pin'; }
	getSubtitle() { return (this.data.direction || '') + ' #' + this.data.port; }
	getIconClass() { return 'md md-link'; }
	getParent() { return this.entityManager.get<RelayDevice>(EntityTypes.RelayDevice, this.data.relay_device_id); }
	getSort() { return [this.data.direction, this.data.port]; }
	// getTags() { return ['equipment', 'assign-tree', 'assignables-tree']; }

	// copyToArea(area: Area) {
	// 	const data = Mangler.clone(this.data);
	// 	data.id = area.entityManager.getAutoId();
	// 	data.area_id = area.data.id;
	// 	data.building_id = area.entityManager.getBuilding().data.id;
	// 	return area.createEntity(data);
	// }

	// canMove() { return true; }

	// moveToArea(area: Area) {
	// 	if (area.entityManager === this.entityManager) {
	// 		this.data.area_id = area.data.id;
	// 		this.refresh();
	// 		return true;
	// 	}
	// 	return false;
	// }

	// getAssignedTo() {
	// 	const bs = this.entityManager.get<BuildingServer>(EntityTypes.BuildingServer, this.data.building_server_id);
	// 	return bs ? [bs] : [];
	// }

	isUnassigned() {
		return !this.assigned.length;
	}

	// isAssignableTo(entity: Entity) {
	// 	return EntityTypes.isBuildingServer(entity);
	// }

	// assignTo(entity: Entity) {
	// 	if (EntityTypes.isBuildingServer(entity)) {
	// 		this.data.building_server_id = entity.data.id;
	// 		this.refresh();
	// 	}
	// }

	// unassignFrom(entity: Entity) {
	// 	if (this.isAssignedTo(entity) && EntityTypes.isBuildingServer(entity)) {
	// 		this.data.building_server_id = null;
	// 		this.refresh();
	// 	}
	// }

	// getAreaDescription() {
	// 	const area = this.closest(EntityTypes.Area);
	// 	return area ? area.getDescription() : '';
	// }

}
