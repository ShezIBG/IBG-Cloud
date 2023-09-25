import { Entity } from './entity';
import { Pipe, PipeTransform } from '@angular/core';

@Pipe({
	name: 'tag'
})
export class TagPipe implements PipeTransform {

	transform(array: Entity[], tags: any): any {
		return array.filter(entity => entity.hasTag(tags));
	}

}
