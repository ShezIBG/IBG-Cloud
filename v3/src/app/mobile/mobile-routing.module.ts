import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { MobileBuildingHomeComponent } from './mobile-building-home/mobile-building-home.component';
import { MobileControlDevicesComponent } from './mobile-control-devices/mobile-control-devices.component';
import { MobileControlComponent } from './mobile-control/mobile-control.component';
import { MobileElectricityComponent } from './mobile-electricity/mobile-electricity.component';
import { MobileHomeComponent } from './mobile-home/mobile-home.component';
import { MobileSelectBuildingComponent } from './mobile-select-building/mobile-select-building.component';
import { MobileComponent } from './mobile/mobile.component';

const routes: Routes = [{
	path: '', component: MobileComponent, children: [
		{ path: '', pathMatch: 'full', component: MobileHomeComponent, data: { hideMobileBack: true, hideMobileHome: true } },
		{ path: 'select-building', component: MobileSelectBuildingComponent },
		{ path: ':buildingId', component: MobileBuildingHomeComponent, data: { hideMobileBack: true, hideMobileHome: true } },
		{ path: ':buildingId/electricity', component: MobileElectricityComponent },
		{ path: ':buildingId/control', component: MobileControlComponent },
		{ path: ':buildingId/control/devices', component: MobileControlDevicesComponent }
	]
}];

@NgModule({
	imports: [RouterModule.forChild(routes)],
	exports: [RouterModule]
})
export class MobileRoutingModule { }
