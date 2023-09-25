import { FormsModule } from '@angular/forms';
import { SharedModule } from './../shared/shared.module';
import { CalendarModule, InputSwitchModule } from 'primeng/primeng';
import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { AmazingTimePickerModule } from 'amazing-time-picker';

import { LightingRoutingModule } from './lighting-routing.module';
import { LightingComponent } from './lighting/lighting.component';
import { LightingOverviewComponent } from './lighting-overview/lighting-overview.component';
import { LightingBuildingComponent } from './lighting-building/lighting-building.component';
import { LightingSchedulesComponent } from './lighting-schedules/lighting-schedules.component';
import { LightingAddScheduleModalComponent } from './lighting-add-schedule-modal/lighting-add-schedule-modal.component';
import { LightingDevicesComponent } from './lighting-devices/lighting-devices.component';
import { LightingDeviceDetailsModalComponent } from './lighting-device-details-modal/lighting-device-details-modal.component';
import { LightingTestScheduleModalComponent } from './lighting-test-schedule-modal/lighting-test-schedule-modal.component';

@NgModule({
	imports: [
		CalendarModule,
		InputSwitchModule,
		CommonModule,
		SharedModule,
		FormsModule,
		LightingRoutingModule,
		AmazingTimePickerModule
	],
	declarations: [
		LightingComponent,
		LightingOverviewComponent,
		LightingBuildingComponent,
		LightingDevicesComponent,
		LightingSchedulesComponent,
		LightingAddScheduleModalComponent,
		LightingDeviceDetailsModalComponent,
		LightingTestScheduleModalComponent
	],
	entryComponents: [
		LightingAddScheduleModalComponent,
		LightingDeviceDetailsModalComponent,
		LightingTestScheduleModalComponent
	]
})
export class LightingModule { }
