import { EntityTypes } from './entity-types';
import { Entity } from './entity';

export class MBusCatalogue extends Entity {

	static type = EntityTypes.MBusCatalogue;
	static groupName = 'M-Bus Catalogue Items';

	isAssignableToMeter() { return !!this.data.assign_to_meter; }
	isAssignableToABB() { return !!this.data.assign_to_abb; }

	hasUtility(utility: string) {
		return !this.data.utility_type || this.data.utility_type === utility;
	}

	getOutputs() {
		return parseInt(this.data.no_of_outputs, 10) || 1;
	}

}
