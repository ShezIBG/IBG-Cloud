import { EntityTypes } from './entity-types';
import { CT } from './ct';
import { EntityManager } from './entity-manager';
import { Gateway } from './gateway';
import { Area } from './area';
import { Entity, MovableEntity, ActivatableEntity } from './entity';
import { Meter } from './meter';

declare var Mangler: any;

export class PM12 extends Entity implements MovableEntity, ActivatableEntity {

	static type = EntityTypes.PM12;
	static groupName = 'PM12s';

	get isActive() { return this.data.active === 1; }
	set isActive(value) { this.data.active = value ? 1 : 0; }

	cts: Entity[] = [];

	get useAsMeter() { return !!this.data.meter_id; }
	set useAsMeter(value) {
		let meter = this.getMeter();

		if (value) {
			// Add meter record
			if (meter) return;

			meter = this.entityManager.createEntity({
				entity: 'meter',
				description: this.getDescription(),
				meter_type: 'E',
				meter_direction: 'import',
				is_supply_meter: 0,
				meter_is_mid_approved: 1,
				monitoring_is_mid_approved: this.hasBus(Entity.BUS_TYPE_MODBUS) ? 1 : 0,
				monitoring_bus_type: this.data.bus_type,
				monitoring_device_type: 'pm12',
				area_id: this.data.area_id,
				virtual_area_id: null,
				parent_id: null
			}) as Meter;

			if (meter) this.data.meter_id = meter.data.id;

		} else {
			// Delete meter record
			if (meter && !meter.canDelete()) return;

			meter.delete();
			this.data.meter_id = 0;
		}

		this.refresh();
	}

	constructor(data, entityManager: EntityManager) {
		super(data, entityManager);

		// Subscribe to its own event to refresh
		this.onItemAddedEvent.subscribe(entity => this.refreshCTs());
		this.onItemRemovedEvent.subscribe(entity => this.refreshCTs());

		this.refreshCTs();
	}

	getTypeDescription() { return 'PM12'; }
	getIconClass() { return 'md md-flash-on'; }
	getParent() { return this.entityManager.get<Area>(EntityTypes.Area, this.data.area_id); }
	getSort() { return this.data.description; }
	getTags() { return ['equipment', 'assign-tree', 'assignables-tree']; }

	hasBus(type: string): boolean { return type === Entity.BUS_TYPE_MODBUS; }
	getBusID(type: string): any { return type === Entity.BUS_TYPE_MODBUS ? this.data.modbus_id : null; }

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

	getAssignedToInfo(entity: Entity) {
		if (!this.isAssignedTo(entity)) return '';
		return EntityTypes.isGateway(entity) ? entity.getDescription() + ', modbus #' + this.data.modbus_id : entity.getDescription();
	}

	isUnassigned() {
		return !this.data.gateway_id;
	}

	isAssignableTo(entity: Entity) {
		return EntityTypes.isGateway(entity) && entity.data.type === 'EC10';
	}

	assignTo(entity: Entity, options: any = {}) {
		if (EntityTypes.isGateway(entity) && entity.data.type === 'EC10' && options.modbus_id) {
			this.data.gateway_id = entity.data.id;
			this.data.modbus_id = options.modbus_id;
			this.refresh();
		}
	}

	unassignFrom(entity: Entity) {
		if (this.isAssignedTo(entity) && EntityTypes.isGateway(entity)) {
			this.data.gateway_id = null;
			this.data.modbus_id = 0;
			this.refresh();
		}
	}

	refreshCTs() {
		this.cts = [];
		for (let i = 0; i < 12; i++) this.cts.push(null);
		this.items.forEach((entity: CT) => {
			if (EntityTypes.isCT(entity) && entity.data.pm12_pin_id) {
				this.cts[entity.data.pm12_pin_id - 1] = entity;
			}
		});
	}

	hasCTs() {
		let ok = false;
		this.items.forEach(entity => {
			if (EntityTypes.isCT(entity) && entity.data.pm12_pin_id) {
				ok = true;
			}
		});
		return ok;
	}

	is3P() {
		return this.data.phase === '3';
	}

	get canToggleMeter() {
		const meter = this.getMeter();
		return !meter || meter.canDelete();
	}

	getMeter(): Meter {
		if (this.data.meter_id) {
			return this.entityManager.get<Meter>(EntityTypes.Meter, this.data.meter_id) || null;
		}
		return null;
	}

	canUpdateLocation() {
		// Can only update 1P device without assigned CTs, or blank 3P devices without CTs
		if (!this.items.length) return true;
		if (!this.is3P()) {
			let ok = true;
			this.items.forEach(ct => {
				if (EntityTypes.isCT(ct) && !ct.canUpdateLocation()) ok = false;
			});
			return ok;
		}

		return false;
	}

	updateLocation(location) {
		if (!this.canUpdateLocation()) return false;

		this.data.location = location;
		this.data.phase = this.data.location === 'L1,2,3' ? '3' : '12';

		// Loop through blank CTs and change their location
		if (!this.is3P()) {
			this.items.forEach(ct => {
				if (EntityTypes.isCT(ct)) ct.updateLocation(location);
			});
		}

		return true;
	}

	getPinDescription(pin: number, phase: string) {
		const group = Math.floor((pin - 1) / 3 + 1);

		if (phase === '3') {
			if (this.is3P()) {
				// 3P CT on 3P PM12
				switch (group) {
					case 1: return '1-2-3 L123 C1';
					case 2: return '4-5-6 L123 C2';
					case 3: return '7-8-9 L123 C3';
					case 4: return '10-11-12 L123 C4';
				}
				return '';
			} else {
				// 1P CT on 3P PM12
				return '' + pin + ' ' + this.getPinLocation(pin) + 'C' + group + ' ' + (pin * 2 + 12) + ',' + (pin * 2 + 13);
			}
		} else {
			// 1P CT on 1P PM12
			return '' + pin + ' ' + (pin * 2 + 12) + ',' + (pin * 2 + 13);
		}
	}

	getPinShortDescription(pin: number, phase: string) {
		const group = Math.floor((pin - 1) / 3 + 1);

		if (phase === '3') {
			if (this.is3P()) {
				// 3P CT on 3P PM12
				switch (group) {
					case 1: return '1-2-3';
					case 2: return '4-5-6';
					case 3: return '7-8-9';
					case 4: return '10-11-12';
				}
			}
		}
		return '' + pin;
	}

	getPinLocation(pin: number) {
		if (this.is3P()) {
			return 'L' + ((pin - 1) % 3 + 1);
		} else {
			return this.data.location;
		}
	}

	canAdd3PCT(pin) {
		if (!this.is3P()) return false;
		if ((pin - 1) % 3 !== 0) return false;

		// Check if any of the pins are taken
		return !this.cts[pin - 1] && !this.cts[pin] && !this.cts[pin + 1];
	}

}
