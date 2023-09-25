import { Entity } from './entity';
import { Pipe, PipeTransform } from '@angular/core';

@Pipe({
	name: 'assignables'
})
export class AssignablesPipe implements PipeTransform {

	transform(array: Entity[], assignParent: Entity, mode: string): any {
		switch (mode) {
			case 'all-assignable':
				return array.filter(entity => {
					return entity.isAssignableTo(assignParent) || entity.hasAssignableItemsTo(assignParent);
				});

			case 'unassigned':
				return array.filter(entity => {
					return entity.canAssignTo(assignParent) || entity.hasAssignableItemsTo(assignParent, true);
				});

			case 'all-unassigned':
				return array.filter(entity => {
					return entity.isUnassigned() || entity.hasUnassignedItems();
				});
		}
	}

}
