import { EntityTypes } from './entity-types';
import { MBusCatalogue } from './mbus-catalogue';
import { MBusMaster } from './mbus-master';
import { Entity } from './entity';

export class MBusDevice extends Entity {

	static type = EntityTypes.MBusDevice;
	static groupName = 'M-Bus Devices';

	get value_multiplier() { return this.data.value_multiplier; }
	set value_multiplier(value) { this.data.value_multiplier = parseFloat(value) || 0; }

	getTypeDescription() { return 'M-Bus Device'; }
	getDescription() { return this.data.description; }
	getIconClass() { return 'md md-adjust'; }
	getSort() { return this.data.description; }

	hasBus(type: string): boolean { return type === Entity.BUS_TYPE_MBUS; }
	getBusID(type: string): any { return (type === Entity.BUS_TYPE_MBUS && this.data.mbus_id) ? this.data.mbus_id : null; }

	getCatalogueItem() {
		return this.entityManager.get<MBusCatalogue>(EntityTypes.MBusCatalogue, this.data.catalogue_id);
	}

	getMBusMaster() {
		return this.data.mbus_master_id ? this.entityManager.get<MBusMaster>(EntityTypes.MBusMaster, this.data.mbus_master_id) : null;
	}

}
