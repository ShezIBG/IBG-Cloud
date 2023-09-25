import { EntityTypes } from './../entity-types';
import { CT } from './../ct';
import { Entity } from './../entity';
import { ScreenService } from './../../screen/screen.service';
import { AppService } from './../../app.service';
import { PM12 } from './../pm12';
import { Component, OnInit, Input } from '@angular/core';
import { EntityAssignComponent } from '../../entity-decorators';

@Component({
	selector: 'app-pm12-assign',
	templateUrl: './pm12-assign.component.html'
})
@EntityAssignComponent(PM12)
export class PM12AssignComponent implements OnInit {

	@Input() entity: PM12 = null;

	hovered;

	constructor(public app: AppService, public screen: ScreenService) { }

	ngOnInit() {
		if (!this.entity) this.entity = this.screen.treeEntity as PM12;
	}

	getAreaDescription(entity: Entity) {
		const area = entity.closest(EntityTypes.Area);
		return area ? area.getDescription() : '';
	}

	assignOne(ct: CT) {
		ct.assignTo(this.screen.assignables.shift());
	}

}
