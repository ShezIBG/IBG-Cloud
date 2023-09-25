import { EntityTypes } from './entity-types';
import { Gateway } from './gateway';
import { RS485Catalogue } from './rs485-catalogue';
import { Entity } from './entity';

export class RS485Device extends Entity {

	static type = EntityTypes.RS485Device;
	static groupName = 'RS-485 Devices';

	get value_multiplier() { return this.data.value_multiplier; }
	set value_multiplier(value) { this.data.value_multiplier = parseFloat(value) || 0; }

	getTypeDescription() { return 'RS-485 Device'; }
	getDescription() { return this.data.description; }
	getIconClass() { return 'md md-adjust'; }
	getSort() { return this.data.description; }

	hasBus(type: string): boolean { return type === Entity.BUS_TYPE_RS485; }
	getBusID(type: string): any { return (type === Entity.BUS_TYPE_RS485 && this.data.rs485_id) ? this.data.rs485_id : null; }

	getCatalogueItem() {
		return this.entityManager.get<RS485Catalogue>(EntityTypes.RS485Catalogue, this.data.catalogue_id);
	}

	getGateway() {
		return this.data.gateway_id ? this.entityManager.get<Gateway>(EntityTypes.Gateway, this.data.gateway_id) : null;
	}

}
