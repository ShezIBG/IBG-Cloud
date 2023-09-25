import { FormsModule } from '@angular/forms';
import { SharedModule } from './../shared/shared.module';
import { CalendarModule, InputSwitchModule } from 'primeng/primeng';
import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { AmazingTimePickerModule } from 'amazing-time-picker';

import { ClimateRoutingModule } from './climate-routing.module';
import { ClimateComponent } from './climate/climate.component';
import { ClimateOverviewComponent } from './climate-overview/climate-overview.component';
import { ClimateBuildingComponent } from './climate-building/climate-building.component';
import { ClimateDevicesComponent } from './climate-devices/climate-devices.component';
import { ClimateSchedulesComponent } from './climate-schedules/climate-schedules.component';
import { ClimateAddScheduleModalComponent } from './climate-add-schedule-modal/climate-add-schedule-modal.component';
import { ClimateDeviceDetailsModalComponent } from './climate-device-details-modal/climate-device-details-modal.component';

@NgModule({
	imports: [
		CalendarModule,
		InputSwitchModule,
		CommonModule,
		SharedModule,
		FormsModule,
		ClimateRoutingModule,
		AmazingTimePickerModule
	],
	declarations: [
		ClimateComponent,
		ClimateOverviewComponent,
		ClimateBuildingComponent,
		ClimateDevicesComponent,
		ClimateSchedulesComponent,
		ClimateAddScheduleModalComponent,
		ClimateDeviceDetailsModalComponent
	],
	entryComponents: [
		ClimateAddScheduleModalComponent,
		ClimateDeviceDetailsModalComponent
	]
})
export class ClimateModule { }
