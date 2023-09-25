import { FormsModule } from '@angular/forms';
import { SharedModule } from './../shared/shared.module';
import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { CalendarModule } from 'primeng/primeng';

import { EmergencyRoutingModule } from './emergency-routing.module';
import { EmergencyOverviewComponent } from './emergency-overview/emergency-overview.component';
import { EmergencyBuildingComponent } from './emergency-building/emergency-building.component';
import { EmergencyComponent } from './emergency/emergency.component';
import { EmergencyGroupsComponent } from './emergency-groups/emergency-groups.component';
import { EmergencyLightsComponent } from './emergency-lights/emergency-lights.component';
import { EmergencyLightInfoModalComponent } from './emergency-light-info-modal/emergency-light-info-modal.component';
import { EmergencyFaultsComponent } from './emergency-faults/emergency-faults.component';

@NgModule({
	imports: [
		CalendarModule,
		CommonModule,
		SharedModule,
		FormsModule,
		EmergencyRoutingModule
	],
	declarations: [
		EmergencyOverviewComponent,
		EmergencyBuildingComponent,
		EmergencyComponent,
		EmergencyGroupsComponent,
		EmergencyLightsComponent,
		EmergencyLightInfoModalComponent,
		EmergencyFaultsComponent
	],
	entryComponents: [
		EmergencyLightInfoModalComponent
	]
})
export class EmergencyModule { }
