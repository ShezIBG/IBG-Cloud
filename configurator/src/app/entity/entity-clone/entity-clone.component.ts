import { EntityCloneModalComponent } from './../entity-clone-modal/entity-clone-modal.component';
import { AppService } from './../../app.service';
import { Entity } from './../entity';
import { Component, Input } from '@angular/core';

@Component({
	selector: 'entity-clone',
	templateUrl: './entity-clone.component.html'
})
export class EntityCloneComponent {

	@Input() entity: Entity;
	@Input() type = 'button';

	constructor(public app: AppService) { }

	clone() {
		this.app.modal.open(EntityCloneModalComponent, { entity: this.entity });
	}

}
