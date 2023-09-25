import { EntityTypes } from './entity-types';
import { RS485Device } from './rs485-device';
import { MBusDevice } from './mbus-device';
import { Meter } from './meter';
import { EntityManager } from './entity-manager';
import { Gateway } from './gateway';
import { Area } from './area';
import { Entity, MovableEntity, ActivatableEntity } from './entity';

declare var Mangler: any;

export class ABBMeter extends Entity implements MovableEntity, ActivatableEntity {

	static type = EntityTypes.ABBMeter;
	static groupName = 'ABB Meters';

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
				monitoring_device_type: 'abb',
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

	get bus_type() { return this.data.bus_type; }
	set bus_type(value) {
		this.data.bus_type = value;

		const meter = this.getMeter();
		if (meter) meter.data.monitoring_bus_type = value;
	}

	get description() { return this.data.description; }
	set description(value) {
		// Called when description changes, applies changes to auto-generated CTs
		this.items.forEach(ct => {
			if (EntityTypes.isCT(ct)) {
				let ld = ct.data.long_description;
				if (ct.is3P()) ld = ld.replace(/\sL1$|\sL2$|\sL3$/, '');

				// Don't update description if it's been previously customised
				if (ld === this.data.description) {
					if (this.is3P()) {
						ct.data.long_description = value + ' ' + ct.data.location;
					} else {
						ct.data.long_description = value;
					}
				}
			}
		});

		this.data.description = value;
	}

	get canToggleMeter() {
		const meter = this.getMeter();
		return !meter || meter.canDelete();
	}

	constructor(data, entityManager: EntityManager) {
		super(data, entityManager);

		// Subscribe to its own event to refresh
		this.onItemAddedEvent.subscribe(entity => this.refreshCTs());
		this.onItemRemovedEvent.subscribe(entity => this.refreshCTs());

		this.refreshCTs();
	}

	getTypeDescription() { return 'ABB Meter'; }
	getIconClass() { return 'md md-flash-auto'; }
	getParent() { return this.entityManager.get<Area>(EntityTypes.Area, this.data.area_id); }
	getSort() { return this.data.description; }
	getTags() { return ['structure', 'equipment', 'assign-tree', 'assignables-tree']; }

	hasBus(type: string): boolean { return type === this.data.bus_type; }
	getBusID(type: string): any {
		if (this.hasBus(Entity.BUS_TYPE_MODBUS)) {
			return this.data.modbus_id || null;
		} else if (this.hasBus(Entity.BUS_TYPE_MBUS)) {
			const device = this.getMBusDevice();
			return device ? device.getBusID(Entity.BUS_TYPE_MBUS) : null;
		} else if (this.hasBus(Entity.BUS_TYPE_RS485)) {
			const device = this.getRS485Device();
			return device ? device.getBusID(Entity.BUS_TYPE_RS485) : null;
		}
		return null;
	}

	copyToArea(area: Area) {
		const data = Mangler.clone(this.data);
		data.id = area.entityManager.getAutoId();
		data.area_id = area.data.id;
		data.meter_id = 0;
		const abb = area.createEntity(data) as ABBMeter;

		// Auto-create ABB CTs
		if (abb.is3P()) {
			// 3P CTs

			const mainCT = abb.entityManager.createEntity({
				entity: 'ct',
				id: abb.entityManager.getAutoId(),
				abb_meter_id: abb.data.id,
				abb_meter_pin_id: 1,
				abb_meter_pin: '1 L1',
				location: 'L1',
				long_description: abb.data.description + ' L1',
				short_description: 'ABB 3P L1'
			});

			mainCT.data.ct_group_id = mainCT.data.id;

			abb.entityManager.createEntity({
				entity: 'ct',
				id: abb.entityManager.getAutoId(),
				abb_meter_id: abb.data.id,
				abb_meter_pin_id: 2,
				abb_meter_pin: '2 L2',
				location: 'L2',
				long_description: abb.data.description + ' L2',
				short_description: 'ABB 3P L2',
				ct_group_id: mainCT.data.id
			});

			abb.entityManager.createEntity({
				entity: 'ct',
				id: abb.entityManager.getAutoId(),
				abb_meter_id: abb.data.id,
				abb_meter_pin_id: 3,
				abb_meter_pin: '3 L3',
				location: 'L3',
				long_description: abb.data.description + ' L3',
				short_description: 'ABB 3P L3',
				ct_group_id: mainCT.data.id
			});
		} else {
			// 1P CT

			const mainCT = abb.entityManager.createEntity({
				entity: 'ct',
				id: abb.entityManager.getAutoId(),
				abb_meter_id: abb.data.id,
				abb_meter_pin_id: 1,
				abb_meter_pin: 1,
				location: abb.data.location,
				long_description: abb.data.description,
				short_description: 'ABB 1P ' + abb.data.location
			});
		}

		return abb;
	}

	canMove() { return true; }

	moveToArea(area: Area) {
		if (area.entityManager === this.entityManager) {
			this.data.area_id = area.data.id;
			this.refresh();

			const meter = this.getMeter();
			if (meter) meter.moveToArea(area);

			return true;
		}

		return false;
	}

	canDelete() {
		// Ignore CTs, check for other items and assignments
		if (this.assigned.length) return false;
		if (this.getMBusDevice()) return false;
		if (this.getRS485Device()) return false;

		const meter = this.getMeter();
		if (meter && !meter.canDelete()) return false;

		let ok = true;
		this.items.forEach(entity => {
			if (EntityTypes.isCT(entity)) {
				// Check if CTs can be deleted
				if (!entity.canDelete()) ok = false;
			} else {
				ok = false;
			}
		});
		return ok;
	}

	beforeDelete() {
		// Delete auto-generated CTs
		this.items.slice().forEach(item => this.entityManager.deleteEntity(item));

		// Delete auto-generated meter record
		const meter = this.getMeter();
		if (meter) meter.delete();
	}

	getAssignedTo() {
		let entity;
		const result = [];

		if (this.data.gateway_id) {
			entity = this.entityManager.get<Gateway>(EntityTypes.Gateway, this.data.gateway_id);
			if (entity) result.push(entity);
		}

		const mbDevice = this.getMBusDevice();
		if (mbDevice) {
			const master = mbDevice.getMBusMaster();
			if (master) result.push(master);
		}

		const rsDevice = this.getRS485Device();
		if (rsDevice) {
			const gateway = rsDevice.getGateway();
			if (gateway) result.push(gateway);
		}

		return result;
	}

	getAssignedToInfo(entity: Entity) {
		if (!this.isAssignedTo(entity)) return '';
		if (EntityTypes.isGateway(entity)) {
			if (this.hasBus(Entity.BUS_TYPE_MODBUS) && this.getBusID(Entity.BUS_TYPE_MODBUS)) {
				return entity.getDescription() + ', modbus #' + this.getBusID(Entity.BUS_TYPE_MODBUS);
			} else if (this.hasBus(Entity.BUS_TYPE_RS485) && this.getBusID(Entity.BUS_TYPE_RS485)) {
				return entity.getDescription() + ', RS-485 #' + this.getBusID(Entity.BUS_TYPE_RS485);
			}
		} else if (EntityTypes.isMBusMaster(entity)) {
			return entity.getDescription() + ', M-Bus #' + this.getBusID(Entity.BUS_TYPE_MBUS);
		}
		return entity.getDescription();
	}

	isUnassigned() {
		return !this.getBusID(this.data.bus_type);
	}

	isAssignableTo(entity: Entity) {
		if (EntityTypes.isGateway(entity) && entity.data.type === 'ABB10') {
			return this.hasBus(Entity.BUS_TYPE_MODBUS);
		} else if (EntityTypes.isGateway(entity) && entity.data.type === 'RS32') {
			return this.hasBus(Entity.BUS_TYPE_RS485);
		} else if (EntityTypes.isMBusMaster(entity)) {
			return this.hasBus(Entity.BUS_TYPE_MBUS);
		}
		return false;
	}

	assignTo(entity: Entity, options: any = {}) {
		if (EntityTypes.isGateway(entity) && entity.data.type === 'ABB10' && this.hasBus(Entity.BUS_TYPE_MODBUS) && options.modbus_id) {
			this.data.gateway_id = entity.data.id;
			this.data.modbus_id = options.modbus_id;
			this.refresh();
		} else if (EntityTypes.isGateway(entity) && entity.data.type === 'RS32') {
			if (!options.catalogue) return;

			// Create RS485Device
			this.entityManager.createEntity({
				entity: 'rs485',
				rs485_id: options.rs485_id,
				value_multiplier: options.value_multiplier,
				description: options.catalogue.getDescription(),
				catalogue_id: options.catalogue.data.id,
				gateway_id: entity.data.id,
				area_id: this.data.area_id,
				abb_meter_id: this.data.id,
				unit: options.unit,
				active: 1
			});

			this.refresh();
		} else if (EntityTypes.isMBusMaster(entity)) {
			if (!options.catalogue) return;

			// Create MBusDevice
			this.entityManager.createEntity({
				entity: 'mbus_device',
				mbus_id: options.mbus_id,
				value_multiplier: options.value_multiplier,
				description: options.catalogue.getDescription(),
				catalogue_id: options.catalogue.data.id,
				mbus_master_id: entity.data.id,
				area_id: this.data.area_id,
				abb_meter_id: this.data.id,
				output_no: options.output_no,
				unit: options.unit,
				active: 1
			});

			this.refresh();
		}
	}

	unassignFrom(entity: Entity) {
		if (!this.isAssignedTo(entity)) return;

		if (EntityTypes.isGateway(entity) && entity.data.type === 'ABB10') {
			this.data.gateway_id = null;
			this.data.modbus_id = 0;
			this.refresh();
		} else if (EntityTypes.isGateway(entity) && entity.data.type === 'RS32') {
			this.getRS485Device().delete();
			this.refresh();
		} else if (EntityTypes.isMBusMaster(entity)) {
			this.getMBusDevice().delete();
			this.refresh();
		}
	}

	refreshCTs() {
		this.cts = [];
		this.items.forEach(entity => {
			if (EntityTypes.isCT(entity) && entity.isMainCT()) {
				this.cts.push(entity);
			}
		});
	}

	canUpdateLocation() {
		// Can only update 1P device without assigned CTs
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

	getMBusDevice(): MBusDevice {
		return this.entityManager.findOne<MBusDevice>(EntityTypes.MBusDevice, { abb_meter_id: this.data.id });
	}

	getRS485Device(): RS485Device {
		return this.entityManager.findOne<RS485Device>(EntityTypes.RS485Device, { abb_meter_id: this.data.id });
	}

	getMeter(): Meter {
		if (this.data.meter_id) {
			return this.entityManager.get<Meter>(EntityTypes.Meter, this.data.meter_id) || null;
		}
		return null;
	}

	is3P() {
		return this.data.phase === '3';
	}

}
