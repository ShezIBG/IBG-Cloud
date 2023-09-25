import { Area } from './area';
import { EntityTypes } from './entity-types';
import { Entity } from './entity';
import { Pipe, PipeTransform } from '@angular/core';

@Pipe({
	name: 'assignFilter'
})
export class AssignFilterPipe implements PipeTransform {

	private areaHasChildren(entity: Area) {
		if (!entity.assigned.length) return false;

		let meterCount = 0;
		entity.assigned.forEach(e => { if (EntityTypes.isMeter(e)) meterCount++; });
		return entity.assigned.length - meterCount > 0;
	}

	transform(array: Entity[]): any {
		return array.filter(entity => {
			if (EntityTypes.isArea(entity)) return this.areaHasChildren(entity);

			if (EntityTypes.isFloor(entity)) {
				let areasWithChildren = 0;
				entity.assigned.forEach(area => {
					if (EntityTypes.isArea(area) && this.areaHasChildren(area)) areasWithChildren += 1;
				});
				return !!areasWithChildren;
			}

			return true;
		});
	}

}
