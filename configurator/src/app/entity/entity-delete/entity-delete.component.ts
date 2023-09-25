import { Entity } from './../entity';
import { Component, Input } from '@angular/core';

@Component({
	selector: 'entity-delete',
	templateUrl: './entity-delete.component.html'
})
export class EntityDeleteComponent {

	@Input() entity: Entity;
	@Input() type = 'button';

}
