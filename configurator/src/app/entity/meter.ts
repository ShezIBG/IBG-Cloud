import { CalculatedMeter } from './calculated-meter';
import { Gateway } from './gateway';
import { EntityTypes } from './entity-types';
import { MySQLDateToISOPipe } from './../mysql-date-to-iso.pipe';
import { RS485Device } from './rs485-device';
import { EntitySortPipe } from './entity-sort.pipe';
import { MBusDevice } from './mbus-device';
import { Area } from './area';
import { Entity, MovableEntity } from './entity';

declare var Mangler: any;

export class Meter extends Entity implements MovableEntity {

	static type = EntityTypes.Meter;
	static groupName = 'Meters';

	previousParentId = null;

	private initDateBacking = undefined;
	get meter_install_date() {
		if (this.initDateBacking === undefined) this.initDateBacking = MySQLDateToISOPipe.stringToDate(this.data.meter_install_date);
		return this.initDateBacking;
	}
	set meter_install_date(value) {
		this.initDateBacking = value;
		this.data.meter_install_date = MySQLDateToISOPipe.dateToString(value, false);
	}

	get monitoring_start_date() {
		if (this.initDateBacking === undefined) this.initDateBacking = MySQLDateToISOPipe.stringToDate(this.data.monitoring_start_date);
		return this.initDateBacking;
	}
	set monitoring_start_date(value) {
		this.initDateBacking = value;
		this.data.monitoring_start_date = MySQLDateToISOPipe.dateToString(value, false);
	}

	get is_submeter(): boolean { return !!this.data.parent_id; }
	set is_submeter(value: boolean) {
		if (this.data.parent_id) {
			this.previousParentId = this.data.parent_id;
			this.data.parent_id = null;
		} else {
			const parents = this.getAvailableParentMeters();
			let newParent;
			if (this.previousParentId) newParent = Mangler.find(parents, { id: this.previousParentId })[0];
			if (!newParent) newParent = parents[0];
			if (newParent) this.data.parent_id = newParent.data.id;
		}
		this.refresh();
	}

	// set is_supply_meter() { this.data.is_supply_meter = false}
	get is_supply_meter() { return !!this.data.is_supply_meter; }
	set is_supply_meter(value) { this.data.is_supply_meter = value ? 1 : 0;}

	get meter_is_mid_approved() { return !!this.data.meter_is_mid_approved; }
	set meter_is_mid_approved(value) { this.data.meter_is_mid_approved = value ? 1 : 0; }

	get monitoring_is_mid_approved() { return !!this.data.monitoring_is_mid_approved; }
	set monitoring_is_mid_approved(value) { this.data.monitoring_is_mid_approved = value ? 1 : 0; }

	get is_visible() { return !!this.data.visible; }
	set is_visible(value) { this.data.visible = value ? 1 : 0; }

	get calculated_meter() { return this.entityManager.findOne<CalculatedMeter>(EntityTypes.CalculatedMeter, { calculated_meter_id: this.data.id }); }

	get monitoring_bus_type() { return this.data.monitoring_bus_type; }
	set m_bus_type(value){this.data.monitoring_device_type = value}
	set monitoring_bus_type(value) {
		if (this.data.monitoring_bus_type !== value && value === 'calculated') {
			// Being switched into calculated mode, create CalculatedMeter object
			this.entityManager.createEntity({
				entity: 'calculated_meter',
				calculated_meter_id: this.data.id,
				meter_id_a: 0,
				meter_id_b: 0,
				meter_id_c: 0,
				meter_id_d: 0,
				meter_id_e: 0,
				meter_id_f: 0,
				meter_id_g: 0,
				meter_id_h: 0,
				meter_id_i: 0,
				meter_id_j: 0,
				meter_id_k: 0,
				operator: 'subtract'
			});
		}

		if (this.data.monitoring_bus_type === 'calculated' && value !== 'calculated') {
			// Calculated mode being switched off, remove CalculatedMeter object
			if (this.calculated_meter) this.calculated_meter.delete();
		}
		this.data.monitoring_bus_type = value;
	}

	

	get hasCalculations() {
		const cm = this.calculated_meter;
		if (!cm) return false;
		return cm.data.meter_id_a || cm.data.meter_id_b || cm.data.meter_id_c || cm.data.meter_id_d || cm.data.meter_id_e || cm.data.meter_id_f || cm.data.meter_id_g || cm.data.meter_id_h || cm.data.meter_id_i || cm.data.meter_id_j || cm.data.meter_id_k;
	}

	get isUsedInCalculations() {
		return this.entityManager.find<CalculatedMeter>(EntityTypes.CalculatedMeter, {
			$or: [
				{ meter_id_a: this.data.id },
				{ meter_id_b: this.data.id },
				{ meter_id_c: this.data.id },
				{ meter_id_d: this.data.id },
				{ meter_id_e: this.data.id },
				{ meter_id_f: this.data.id },
				{ meter_id_g: this.data.id },
				{ meter_id_h: this.data.id },
				{ meter_id_i: this.data.id },
				{ meter_id_j: this.data.id },
				{ meter_id_k: this.data.id }
			]
		}).length > 0;
	}

	isABB() {
		if (this.data.monitoring_bus_type === 'lora') {
			return false;
		}
		return this.data.monitoring_device_type === 'abb';
	
	}

	// isABB() {
	// 	// Only apply the isABB() condition if the monitoring bus type is neither 'lora' nor 'modbus'
	// 	if (this.data.monitoring_bus_type !== 'lora' && this.data.monitoring_bus_type !== 'modbus') {
	// 	  return this.data.monitoring_device_type === 'abb' || !this.data.monitoring_device_type;
	// 	}
		
	// 	// Return false for 'lora' or 'modbus' bus types
	// 	return false;
	//   }

	getTypeDescription() {

		let desc = this.isABB() ? 'ABB ' : '';

		switch (this.data.meter_type) {
			case 'E': desc += 'Electricity '; break;
			case 'G': desc += 'Gas '; break;
			case 'W': desc += 'Water '; break;
			case 'H': desc += 'Heat '; break;
		}

		return this.data.parent_id ? desc + 'Sub-Meter' : desc + 'Meter';
	}

	get bus_type() { return this.data.bus_type; }
	set bus_type(value) {
		this.data.bus_type = value;

		const meter = this.getMeter();
		if (meter) meter.data.monitoring_bus_type = value;
	}
	

	// handleDeviceTypeChange() {
	// 	if (this.isABB(Entity.DEVICE_TYPE_MODBUS)) {
	// 		return this.data.parent_id = null;
	// 	}

	// }

	getSubtitle() { return this.data.serial_number ? 'Serial ' + this.data.serial_number : ''; }
	getIconClass() { 
		if(this.data.meter_type == 'E'){

			return 'md md-av-elec';
		}
		else if(this.data.meter_type == 'G'){

			return 'md md-av-gas';
		}
		else if(this.data.meter_type == 'W'){

			return 'md md-av-water';
		}
		else if(this.data.meter_type == 'H'){

			return 'md md-av-heat';
		}
		return 'md md-av-timer'; }
	getParent() { return this.entityManager.get<Area>(EntityTypes.Area, this.data.area_id); }
	getSort() { return this.data.description; }
	getTags() { return this.isABB() ? [] : ['structure']; }

	hasBus(type: string): boolean {
		return this.data.monitoring_bus_type === type;
	}

	getBusID(type: string): any {
		let device = null;

		switch (type) {
			case Entity.BUS_TYPE_MBUS:
				device = this.getMBusDevice();
				if (device) return device.getBusID(type);
				break;

			case Entity.BUS_TYPE_RS485:
				device = this.getRS485Device();
				if (device) return device.getBusID(type);
				break;

			case Entity.BUS_TYPE_MODBUS:
				return 1;
		}

		return null;
	}

	beforeDelete() {
		// Force unassign from all CalculatedMeters
		this.assigned.forEach(entity => {
			if (EntityTypes.isCalculatedMeter(entity)) {
				entity.unassignFrom(this);
			}
		});

		// If this meter has a calculated meter record, remove it
		if (this.calculated_meter) this.calculated_meter.delete();
	}

	canDelete() {
		if (this.getMBusDevice()) return false;
		if (this.getRS485Device()) return false;
		return super.canDelete();
	}

	isAssignableTo(entity: Entity) {
		if (EntityTypes.isMBusMaster(entity) && !this.isABB()) {
			return this.hasBus(Entity.BUS_TYPE_MBUS);
		} else if (EntityTypes.isGateway(entity) && !this.isABB()) {
			if (entity.data.type === 'RS32') {
				return entity.hasBus(Entity.BUS_TYPE_RS485) && this.hasBus(Entity.BUS_TYPE_RS485);
			} else if (entity.data.type === 'MAR10') {
				return entity.hasBus(Entity.BUS_TYPE_MODBUS) && this.hasBus(Entity.BUS_TYPE_MODBUS);
			}
		}
		return false;
	}

	getAssignedTo(): Entity[] {
		const assignedTo = [];
		let entity = null;

		entity = this.data.parent_id ? this.entityManager.get<Meter>(EntityTypes.Meter, this.data.parent_id) : null;
		if (entity) assignedTo.push(entity);

		entity = this.data.virtual_area_id ? this.entityManager.get<Area>(EntityTypes.Area, this.data.virtual_area_id) : null;
		if (entity) assignedTo.push(entity);

		const mbDevice = this.getMBusDevice();
		if (mbDevice) {
			const master = mbDevice.getMBusMaster();
			if (master) assignedTo.push(master);
		}

		const rsDevice = this.getRS485Device();
		if (rsDevice) {
			const gateway = rsDevice.getGateway();
			if (gateway) assignedTo.push(gateway);
		}

		// MarCom collectors are directly assigned to the meter
		entity = this.data.gateway_id ? this.entityManager.get<Gateway>(EntityTypes.Gateway, this.data.gateway_id) : null;
		if (entity && entity.data.type === 'MAR10') assignedTo.push(entity);

		return assignedTo;
	}

	getAssignedToInfo(entity: Entity) {
		if (!this.isAssignedTo(entity)) return '';

		if (EntityTypes.isMBusMaster(entity)) {
			return entity.getDescription() + ', M-Bus #' + this.getBusID(Entity.BUS_TYPE_MBUS);
		} else if (EntityTypes.isGateway(entity) && entity.hasBus(Entity.BUS_TYPE_RS485)) {
			return entity.getDescription() + ', RS-485 #' + this.getBusID(Entity.BUS_TYPE_RS485);
		} else {
			return entity.getDescription();
		}
	}

	assignTo(entity: Entity, options: any = {}) {
		if (EntityTypes.isGateway(entity) && entity.data.type === 'RS32') {
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
				meter_id: this.data.id,
				unit: options.unit,
				active: 1
			});

			// Set device type from catalogue item
			this.data.monitoring_device_type = options.catalogue.data.monitoring_device_type;

			this.refresh();
		} else if (EntityTypes.isGateway(entity) && entity.data.type === 'MAR10') {
			this.data.monitoring_device_type = 'marcom';
			this.data.gateway_id = entity.data.id;
			this.refresh();
		} else if (EntityTypes.isArea(entity)) {
			this.data.virtual_area_id = entity.data.id;
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
				meter_id: this.data.id,
				output_no: options.output_no,
				unit: options.unit,
				active: 1
			});

			// Set device type from catalogue item
			this.data.monitoring_device_type = options.catalogue.data.monitoring_device_type;

			this.refresh();
		}
	}

	unassignFrom(entity: Entity) {
		if (!this.isAssignedTo(entity)) return;

		if (EntityTypes.isGateway(entity) && entity.data.type === 'RS32') {
			this.getRS485Device().delete();
			this.data.monitoring_device_type = 'none';
			this.refresh();
		} else if (EntityTypes.isGateway(entity) && entity.data.type === 'MAR10') {
			this.data.monitoring_device_type = 'none';
			this.data.gateway_id = null;
			this.refresh();
		} else if (EntityTypes.isArea(entity)) {
			this.data.virtual_area_id = null;
			this.refresh();
		} else if (EntityTypes.isMBusMaster(entity)) {
			this.getMBusDevice().delete();
			this.data.monitoring_device_type = 'none';
			this.refresh();
		}
	}

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
			if (this.data.virtual_area_id === area.data.id) this.data.virtual_area_id = null;
			this.refresh();
			return true;
		}
		return false;
	}

	getSubMeters(): Meter[] {
		return Mangler.find(this.assigned, { entity: 'meter' });
	}

	getAllSubMeters(): Meter[] {
		let list: Meter[] = this.getSubMeters();
		list.forEach((submeter: Meter) => {
			list = list.concat(submeter.getAllSubMeters());
		});
		return list;
	}

	getAvailableParentMeters() {
		const list = this.entityManager.find<Meter>(EntityTypes.Meter, { meter_type: this.data.meter_type });
		const exclude = this.getAllSubMeters();
		exclude.push(this);

		exclude.forEach(item => {
			const i = list.indexOf(item);
			if (i !== -1) list.splice(i, 1);
		});

		return EntitySortPipe.transform(list);
	}

	getCalculationMeters() {
		const list = this.entityManager.find<Meter>(EntityTypes.Meter, { meter_type: this.data.meter_type, monitoring_bus_type: { $ne: 'calculated' } });
		const exclude = [this];

		exclude.forEach(item => {
			const i = list.indexOf(item);
			if (i !== -1) list.splice(i, 1);
		});

		return EntitySortPipe.transform(list);
	}

	replacedMeterReadingChanged(){
		if (!this.data) return;
		this.data.replaced_meter_reading = (this.data.replaced_meter_reading || '').replace(/\s+/g, '');
	}
	

	getMBusDevice(): MBusDevice {
		return this.entityManager.findOne<MBusDevice>(EntityTypes.MBusDevice, { meter_id: this.data.id });
	}

	getRS485Device(): RS485Device {
		return this.entityManager.findOne<RS485Device>(EntityTypes.RS485Device, { meter_id: this.data.id });
	}

	getMeter(): Meter {
		if (this.data.meter_id) {
			return this.entityManager.get<Meter>(EntityTypes.Meter, this.data.meter_id) || null;
		}
		return null;
	}

}
