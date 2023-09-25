import { Entity } from './entity';
import { Pipe, PipeTransform } from '@angular/core';

declare var window: any;

@Pipe({
	name: 'entitySort'
})
export class EntitySortPipe implements PipeTransform {

	static collator = null;

	static transform(array: Entity[]): any {
		// Create static string comparator
		if (this.collator === null && window.Intl && window.Intl.Collator) {
			this.collator = new Intl.Collator(undefined, { numeric: true, sensitivity: 'base' });
		}

		array.sort((a: Entity, b: Entity) => {
			// Compare types
			if (a.type < b.type) return -1;
			if (a.type > b.type) return 1;

			// Compare sort arrays
			let asort = a.getSort();
			let bsort = b.getSort();
			if (!(asort instanceof Array)) asort = [asort];
			if (!(bsort instanceof Array)) bsort = [bsort];
			const cnt = Math.min(asort.length, bsort.length);
			for (let i = 0; i < cnt; i++) {
				const aitem = asort[i];
				const bitem = bsort[i];
				if (this.collator) {
					const compResult = this.collator.compare(aitem, bitem);
					if (compResult !== 0) return compResult;
				} else {
					if (aitem < bitem) return -1;
					if (aitem > bitem) return 1;
				}
			}
			if (asort.length < bsort.length) return -1;
			if (asort.length > bsort.length) return 1;

			// All passed, must be equal
			// This is not great for entities, as sorting is not stable
			return 0;
		});
		return array;
	}

	transform(array: Entity[], args?: any): any {
		return EntitySortPipe.transform(array);
	}

}
