import { Component, Input } from '@angular/core';
import { Pagination } from '../pagination';

@Component({
	selector: 'app-pagination',
	templateUrl: './pagination.component.html',
	styleUrls: ['./pagination.component.less']
})
export class PaginationComponent {

	@Input() pagination: Pagination;

}
