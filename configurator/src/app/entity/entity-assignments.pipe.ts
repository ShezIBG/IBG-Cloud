import { EntityTypes } from './entity-types';
import { Entity } from './entity';
import { Pipe, PipeTransform } from '@angular/core';

@Pipe({
	name: 'entityAssignments'
})
export class EntityAssignmentsPipe implements PipeTransform {

	static transform(array: Entity[], parent: Entity): any {
		return array.filter(entity => {
			if (EntityTypes.isCT(entity)) return entity.isMainCT();
			if (EntityTypes.isFloor(entity)) return false;
			if (EntityTypes.isArea(entity)) return false;
			if (EntityTypes.isFloorPlanItem(entity)) return false;
			if (parent && EntityTypes.isArea(parent) && EntityTypes.isRouter(entity)) return false;
			if (parent && EntityTypes.isMeter(parent) && EntityTypes.isMeter(entity)) return false;
			return true;
		});
	}

	transform(array: Entity[], parent: Entity): any {
		return EntityAssignmentsPipe.transform(array, parent);
	}

}
