import { Pipe, PipeTransform } from '@angular/core';

@Pipe({
	name: 'itemlist'
})
export class ItemListPipe implements PipeTransform {

	transform(value: any[], object: any, key: string): any {
		setTimeout(() => {
			object[key] = value ? value : [];
		}, 0);
		return value;
	}

}
