import { BuildingServer } from './building-server';
import { Area } from './area';
import { EntityTypes } from './entity-types';
import { Entity, MovableEntity, ActivatableEntity } from './entity';

declare var Mangler: any;

export class DaliLight extends Entity implements MovableEntity, ActivatableEntity {

	static type = EntityTypes.DaliLight;
	static groupName = 'DALI Lights';

	get isActive() { return this.data.active === 1; }
	set isActive(value) { this.data.active = value ? 1 : 0; }

	get no_of_lights() { return this.data.no_of_lights; }
	set no_of_lights(value) { this.data.no_of_lights = parseInt(value, 10) || 1; }

	getTypeDescription() { return 'DALI Light'; }
	getSubtitle() { return 'Subnet ' + this.data.ve_subnet_id + ' #' + this.data.dali_id; }
	getIconClass() { return 'eticon eticon-bulb-alt'; }
	getParent() { return this.entityManager.get<Area>(EntityTypes.Area, this.data.area_id); }
	getSort() { return [this.data.ve_subnet_id, this.data.dali_id, this.data.description]; }
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
