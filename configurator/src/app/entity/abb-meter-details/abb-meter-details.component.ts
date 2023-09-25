import { EntityTypes } from './../entity-types';
import { Entity } from './../entity';
import { Area } from './../area';
import { ABBMeter } from './../abb-meter';
import { CT } from './../ct';
import { AppService } from './../../app.service';
import { ScreenService } from './../../screen/screen.service';
import { Component, Input, OnInit } from '@angular/core';
import { EntityDetailComponent } from '../../entity-decorators';

@Component({
	selector: 'app-abb-meter-details',
	templateUrl: './abb-meter-details.component.html'
})
@EntityDetailComponent(ABBMeter)
export class ABBMeterDetailsComponent implements OnInit {

	@Input() entity: ABBMeter = null;

	// repalcedMeter = [];

	constructor(public app: AppService, public screen: ScreenService) { }

	ngOnInit() {
		if (!this.entity) this.entity = this.screen.detailEntity as ABBMeter;
	}

	getAreaDescription(entity: Entity) {
		const area = entity.closest(EntityTypes.Area);
		return area ? area.getDescription() : '';
	}

	getAreas(floor: Entity) {
		return this.entity.entityManager.find<Area>(EntityTypes.Area, { floor_id: floor.data.id });
	}

	editCT(ct: CT) {
		this.app.modal.open(CT.detailComponents[0], { entity: ct });
	}

	// replacedMeterReadingChanged() {
	// 	if (!this.entity) return;
	// 	this.entity.data.replaced_meter_reading = (this.entity.data.replaced_meter_reading || '').replace(/\s+/g, '');
	// }

	replacedMeterReadingChanged(){
		if (!this.entity) return;

		// Remove spaces and non-numeric characters
		this.entity.data.replaced_meter_reading = this.entity.data.replaced_meter_reading
		.replace(/[^\d.]/g, '');

		// Ensure only one decimal point
		const parts = this.entity.data.replaced_meter_reading.split('.');
		if (parts.length > 2) {
			this.entity.data.replaced_meter_reading = parts[0] + '.' + parts.slice(1).join('');
		}
		
	}

	
	onTimeChange(event: any) {
		// Format the time without triggering two-way binding
		const inputTime = event;
		const timeParts = inputTime.split(':');
		const hour = parseInt(timeParts[0], 10);
		const minute = timeParts[1] ? parseInt(timeParts[1], 10) : 0;
	
		// Format the hour to 24-hour format
		const formattedHour = hour >= 0 && hour <= 23 ? this.addLeadingZero(hour) : '00';
		const formattedMinute = minute >= 0 && minute <= 59 ? this.addLeadingZero(minute) : '00';
	
		// Combine the formatted hour and minute
		const formattedTime = `${formattedHour}:${formattedMinute}`;
	
		// Update the ngModel with the formatted time
		this.entity.data.init_time = formattedTime;
	}
	
	addLeadingZero(num: number): string {
		return num < 10 ? '0' + num : num.toString();
	}

}
