import { FormsModule } from '@angular/forms';
import { SharedModule } from './../shared/shared.module';
import { CalendarModule, InputSwitchModule, SliderModule } from 'primeng/primeng';
import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { AmazingTimePickerModule } from 'amazing-time-picker';

import { ControlRoutingModule } from './control-routing.module';
import { ControlComponent } from './control/control.component';
import { ControlOverviewComponent } from './control-overview/control-overview.component';
import { ControlBuildingComponent } from './control-building/control-building.component';
import { ControlSchedulesComponent } from './control-schedules/control-schedules.component';
import { ControlAddScheduleModalComponent } from './control-add-schedule-modal/control-add-schedule-modal.component';
import { ControlDevicesComponent } from './control-devices/control-devices.component';
import { ControlDeviceDetailsModalComponent } from './control-device-details-modal/control-device-details-modal.component';
import { KnxInputComponent } from './knx-input/knx-input.component';
import { KnxOutputComponent } from './knx-output/knx-output.component';

@NgModule({
	imports: [
		CalendarModule,
		InputSwitchModule,
		CommonModule,
		SharedModule,
		FormsModule,
		ControlRoutingModule,
		AmazingTimePickerModule,
		SliderModule
	],
	declarations: [
		ControlComponent,
		ControlOverviewComponent,
		ControlBuildingComponent,
		ControlDevicesComponent,
		ControlSchedulesComponent,
		ControlAddScheduleModalComponent,
		ControlDeviceDetailsModalComponent,
		KnxInputComponent,
		KnxOutputComponent
	],
	exports: [
		KnxInputComponent,
		KnxOutputComponent
	],
	entryComponents: [
		ControlAddScheduleModalComponent,
		ControlDeviceDetailsModalComponent
	]
})
export class ControlModule { }
