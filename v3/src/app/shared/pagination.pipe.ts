import { Pipe, PipeTransform } from '@angular/core';
import { Pagination } from './pagination';

@Pipe({
	name: 'pagination',
	pure: false // TODO: Any way to do it pure?
})
export class PaginationPipe implements PipeTransform {

	transform(value: any[], pagination: Pagination): any {
		pagination.count = value ? value.length : 0;
		const start = pagination.startPosition();
		return value.slice(start, start + pagination.pageSize);
	}

}
