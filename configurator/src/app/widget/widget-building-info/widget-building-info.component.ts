import { AppService } from './../../app.service';
import { Building } from './../../entity/building';
import { Component } from '@angular/core';

@Component({
	selector: 'widget-building-info',
	templateUrl: './widget-building-info.component.html'
})
export class WidgetBuildingInfoComponent {

	building: Building;

	constructor(public app: AppService) {
		this.building = app.building;
	}

}
