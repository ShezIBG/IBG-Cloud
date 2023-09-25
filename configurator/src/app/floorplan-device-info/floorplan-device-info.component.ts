import { EntityTypes } from './../entity/entity-types';
import { Entity } from './../entity/entity';
import { Component, Input } from '@angular/core';

@Component({
	selector: 'app-floorplan-device-info',
	templateUrl: './floorplan-device-info.component.html',
	styleUrls: ['./floorplan-device-info.component.css']
})
export class FloorplanDeviceInfoComponent {

	@Input() entity;

	getFloorPlanItem() {
		let floorPlanItem = null;
		this.entity.assigned.forEach((item: Entity) => {
			if (EntityTypes.isFloorPlanItem(item)) floorPlanItem = item;
		});
		return floorPlanItem;
	}

}
