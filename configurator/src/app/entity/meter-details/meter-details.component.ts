import { EntityTypes } from './../entity-types';
import { AppService } from './../../app.service';
import { Meter } from './../meter';
import { Area } from './../area';
import { Entity } from './../entity';
import { ScreenService } from './../../screen/screen.service';
import { Component, Input, OnInit } from '@angular/core';
import { EntityDetailComponent } from '../../entity-decorators';

@Component({
	selector: 'app-meter-details',
	templateUrl: './meter-details.component.html'
})
@EntityDetailComponent(Meter)
export class MeterDetailsComponent implements OnInit {

	@Input() entity: Meter = null;

	calculationMeters = [];
	monitoringBusType = [];
	mergedResult = [];
	// handleManga = [];
	messageVisible = false;

	constructor(public screen: ScreenService, private appService: AppService) { }
	
	ngOnInit() {
		if (!this.entity) this.entity = this.screen.detailEntity as Meter;
		this.calculationMeters = this.entity.getCalculationMeters();
		// this.handleManga = this.entity.handleDeviceTypeChange();

		// if (!this.entity.data.monitoring_device_type) {
		// 	this.entity.data.monitoring_device_type = 'abb'; // Set "abb" as the default value
		// }
	}

	getAreaDescription(entity: Entity) {
		const area = entity.closest(EntityTypes.Area);
		return area ? area.getDescription() : '';
	}

	getAreaBreakerId(entity: Entity){
		const area = entity.closest(EntityTypes.Area);
		return area ? area.getBreakerId() : '';
	}

	getAreas(floor: Entity) {
		return this.entity.entityManager.find<Area>(EntityTypes.Area, { floor_id: floor.data.id });
	}

	// getBreakers(breaker: Entity){
	// 	return this.entity.entityManager.find<DistBoard>(EntityTypes.DistBoard, {db_id: });
	// }

	serialChanged() {
		if (!this.entity) return;
		this.entity.data.serial_number = (this.entity.data.serial_number || '').replace(/\s+/g, '');
	}

	mpanChanged() {
		if (!this.entity) return;
		this.entity.data.mpan = (this.entity.data.mpan || '').replace(/\s+/g, '');
	}

	deviceEUIChanged() {
		if (!this.entity) return;
		this.entity.data.device_eui = (this.entity.data.device_eui || '').replace(/\s+/g, '');
	}

	removeWhitespace(event) {
		event.target.value = event.target.value.trim();
	}

	getMonitoringBusType() {
		return this.appService.monitoring_bus_type;
	}

	getMergedResult(queryType: string){
		return this.appService.merged_results.filter((item) => item.queryType === queryType)
	}

	trackByFn(index: number, item: any): any {
		return item.id; // Use a unique identifier from your data, such as the 'id' property
	}

	showMessage(show: boolean) {
		this.messageVisible = show;
	}

	mbosType(){
		return this.entity.data.monitoring_device_type;
	}
	// handleDeviceTypeChange() {
	// 	if (this.entity.isABB()) {
	// 		this.entity.data.parent_id = null;
	// 	}
	// }
	  
	  
	  
}
