import { FormsModule } from '@angular/forms';
import { SharedModule } from './../shared/shared.module';
import { CalendarModule, InputSwitchModule } from 'primeng/primeng';
import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { AmazingTimePickerModule } from 'amazing-time-picker';

import { RelayRoutingModule } from './relay-routing.module';
import { RelayComponent } from './relay/relay.component';
import { RelayOverviewComponent } from './relay-overview/relay-overview.component';
import { RelayBuildingComponent } from './relay-building/relay-building.component';
import { RelaySchedulesComponent } from './relay-schedules/relay-schedules.component';
import { RelayAddScheduleModalComponent } from './relay-add-schedule-modal/relay-add-schedule-modal.component';
import { RelayDeviceDetailsModalComponent } from './relay-device-details-modal/relay-device-details-modal.component';

@NgModule({
	imports: [
		CalendarModule,
		InputSwitchModule,
		CommonModule,
		SharedModule,
		FormsModule,
		RelayRoutingModule,
		AmazingTimePickerModule
	],
	declarations: [
		RelayComponent,
		RelayOverviewComponent,
		RelayBuildingComponent,
		RelaySchedulesComponent,
		RelayAddScheduleModalComponent,
		RelayDeviceDetailsModalComponent
	],
	entryComponents: [
		RelayAddScheduleModalComponent,
		RelayDeviceDetailsModalComponent
	]
})
export class RelayModule { }
