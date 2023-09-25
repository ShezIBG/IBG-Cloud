import { EntityTypes } from './entity-types';
import { Entity } from './entity';

export class Building extends Entity {

	static type = EntityTypes.Building;
	static groupName = 'Buildings';

	getTypeDescription() { return 'Building'; }
	getIconClass() { return 'ei ei-building'; }
	getTags() { return ['structure', 'structure-tree', 'equipment', 'equipment-tree', 'assign-tree', 'floorplan-tree']; }

}
