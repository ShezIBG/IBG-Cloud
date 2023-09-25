import { Pipe, PipeTransform } from '@angular/core';

@Pipe({
	name: 'count'
})
export class CountPipe implements PipeTransform {

	transform(value: any[], object: any, key: string): any {
		object[key] = value ? value.length : 0;
		return value;
	}

}
