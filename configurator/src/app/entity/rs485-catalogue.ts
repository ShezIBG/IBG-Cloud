import { EntityTypes } from './entity-types';
import { Entity } from './entity';

export class RS485Catalogue extends Entity {

	static type = EntityTypes.RS485Catalogue;
	static groupName = 'RS-485 Catalogue Items';

	isAssignableToMeter() { return !!this.data.assign_to_meter; }
	isAssignableToABB() { return !!this.data.assign_to_abb; }

	hasUtility(utility: string) {
		return !this.data.utility_type || this.data.utility_type === utility;
	}

}
