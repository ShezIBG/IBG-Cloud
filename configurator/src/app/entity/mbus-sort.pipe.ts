import { Entity } from './entity';
import { Pipe, PipeTransform } from '@angular/core';

@Pipe({
	name: 'mbusSort'
})
export class MBusSortPipe implements PipeTransform {

	static transform(array: Entity[]): any {
		array.sort((a: Entity, b: Entity) => {
			const aId = a.getBusID(Entity.BUS_TYPE_MBUS);
			const bId = b.getBusID(Entity.BUS_TYPE_MBUS);
			if (aId < bId) return -1;
			if (aId > bId) return 1;
			return 0;
		});
		return array;
	}

	transform(array: Entity[], args?: any): any {
		return MBusSortPipe.transform(array);
	}

}
