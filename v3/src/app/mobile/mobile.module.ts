import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { MobileRoutingModule } from './mobile-routing.module';
import { MobileComponent } from './mobile/mobile.component';
import { MobileHomeComponent } from './mobile-home/mobile-home.component';
import { MobileHeaderComponent } from './mobile-header/mobile-header.component';
import { MobileElectricityComponent } from './mobile-electricity/mobile-electricity.component';
import { MobileControlComponent } from './mobile-control/mobile-control.component';
import { MobileBuildingHomeComponent } from './mobile-building-home/mobile-building-home.component';
import { MobileService } from './mobile.service';
import { MobileSelectBuildingComponent } from './mobile-select-building/mobile-select-building.component';
import { MobileBuildingHeaderComponent } from './mobile-building-header/mobile-building-header.component';
import { SharedModule } from 'app/shared/shared.module';
import { FormsModule } from '@angular/forms';
import { ControlModule } from 'app/control/control.module';
import { MobileControlDevicesComponent } from './mobile-control-devices/mobile-control-devices.component';

@NgModule({
	declarations: [
		MobileComponent,
		MobileHomeComponent,
		MobileHeaderComponent,
		MobileElectricityComponent,
		MobileControlComponent,
		MobileBuildingHomeComponent,
		MobileSelectBuildingComponent,
		MobileBuildingHeaderComponent,
		MobileControlDevicesComponent
	],
	imports: [
		CommonModule,
		MobileRoutingModule,
		SharedModule,
		FormsModule,
		ControlModule
	],
	providers: [
		MobileService
	]
})
export class MobileModule { }
