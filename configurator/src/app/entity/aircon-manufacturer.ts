import { EntityTypes } from './entity-types';
import { Entity } from './entity';

export class AirconManufacturer extends Entity {

	static type = EntityTypes.AirconManufacturer;
	static groupName = 'Aircon Manufacturers';

	getTypeDescription() { return 'Aircon Manufacturer'; }
	getDescription() { return this.data.desc || ''; }

}
