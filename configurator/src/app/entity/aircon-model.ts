import { AirconManufacturer } from './aircon-manufacturer';
import { EntityTypes } from './entity-types';
import { Entity } from './entity';

export class AirconModel extends Entity {

	static type = EntityTypes.AirconModel;
	static groupName = 'Aircon Models';

	getTypeDescription() { return 'Aircon Model'; }
	getParent() { return this.entityManager.get<AirconManufacturer>(EntityTypes.AirconManufacturer, this.data.manufacturer_id); }
	getDescription() { return (this.lastParent ? this.lastParent.getDescription() : '') + ' ' + (this.data.model_series || ''); }

}
