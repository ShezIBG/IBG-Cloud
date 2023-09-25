import { RelayPin } from './relay-pin';
import { Area } from './area';
import { EntityTypes } from './entity-types';
import { Entity, MovableEntity } from './entity';

declare var Mangler: any;

export class RelayEndDevice extends Entity implements MovableEntity {

	static type = EntityTypes.RelayEndDevice;
	static groupName = 'Relay End Devices';

	getTypeDescription() { return 'Relay End Device'; }
	getSubtitle() { return this.data.category; }
	getIconClass() { return 'md md-settings-power'; }
	getParent() { return this.entityManager.get<Area>(EntityTypes.Area, this.data.area_id); }
	getSort() { return this.data.description; }
	getTags() { return ['equipment', 'assign-tree', 'assignables-tree']; }

	copyToArea(area: Area) {
		const data = Mangler.clone(this.data);
		data.id = area.entityManager.getAutoId();
		data.area_id = area.data.id;
		data.building_id = area.entityManager.getBuilding().data.id;
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
		const assignedTo = [];
		let pin;

		if (this.data.state_pin_id) {
			pin = this.entityManager.get<RelayPin>(EntityTypes.RelayPin, this.data.state_pin_id);
			if (pin) assignedTo.push(pin);
		}

		if (this.data.isolator_pin_id) {
			pin = this.entityManager.get<RelayPin>(EntityTypes.RelayPin, this.data.isolator_pin_id);
			if (pin) assignedTo.push(pin);
		}

		return assignedTo;
	}

	isUnassigned() {
		return !this.data.state_pin_id;
	}

	isAssignableTo(entity: Entity) {
		return EntityTypes.isRelayPin(entity);
	}

	assignTo(entity: Entity, options: any = {}) {
		if (EntityTypes.isRelayPin(entity)) {
			switch (options.target) {
				case 'state':
					this.data.state_pin_id = entity.data.id;
					this.refresh();
					break;

				case 'isolator':
					this.data.isolator_pin_id = entity.data.id;
					this.refresh();
					break;
			}
		}
	}

	unassignFrom(entity: Entity) {
		if (this.isAssignedTo(entity) && EntityTypes.isRelayPin(entity)) {
			if (this.data.state_pin_id === entity.data.id) this.data.state_pin_id = null;
			if (this.data.isolator_pin_id === entity.data.id) this.data.isolator_pin_id = null;
			this.refresh();
		}
	}

	getAreaDescription() {
		const area = this.closest(EntityTypes.Area);
		return area ? area.getDescription() : '';
	}

}
