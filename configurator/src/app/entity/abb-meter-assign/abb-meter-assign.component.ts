import { EntityTypes } from './../entity-types';
import { CT } from './../ct';
import { Entity } from './../entity';
import { ScreenService } from './../../screen/screen.service';
import { ABBMeter } from './../abb-meter';
import { Component, OnInit, Input } from '@angular/core';
import { EntityAssignComponent } from '../../entity-decorators';

@Component({
	selector: 'app-abb-meter-assign',
	templateUrl: './abb-meter-assign.component.html'
})
@EntityAssignComponent(ABBMeter)
export class ABBMeterAssignComponent implements OnInit {

	@Input() entity: ABBMeter = null;

	hovered = null;

	constructor(public screen: ScreenService) { }

	ngOnInit() {
		if (!this.entity) this.entity = this.screen.treeEntity as ABBMeter;
	}

	getAreaDescription(entity: Entity) {
		const area = entity.closest(EntityTypes.Area);
		return area ? area.getDescription() : '';
	}

	assignOne(ct: CT) {
		ct.assignTo(this.screen.assignables.shift());
	}

}
