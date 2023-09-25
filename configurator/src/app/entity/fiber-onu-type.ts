import { EntityTypes } from './entity-types';
import { Entity } from './entity';

export class FiberONUType extends Entity {

	static type = EntityTypes.FiberONUType;
	static groupName = 'Fibre ONU Types';

	getTypeDescription() { return 'Fibre ONU Type'; }
	getDescription() { return this.data.description || ''; }

}
