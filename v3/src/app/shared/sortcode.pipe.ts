import { Pipe, PipeTransform } from '@angular/core';

@Pipe({
	name: 'sortcode'
})
export class SortcodePipe implements PipeTransform {

	static fromString(value: any): string {
		return ('' + value).replace(/[^0-9]/g, '').substr(0, 6);
	}

	static toList(value: any): string[] {
		value = this.fromString(value);
		if (value.length <= 2) {
			return [value.substr(0, 2)];
		} else if (value.length <= 4) {
			return [value.substr(0, 2), value.substr(2, 2)];
		} else {
			return [value.substr(0, 2), value.substr(2, 2), value.substr(4, 2)];
		}
	}

	static transform(value: any): string {
		return this.toList(value).join('-');
	}

	transform(value: any): string {
		return SortcodePipe.transform(value);
	}

}
