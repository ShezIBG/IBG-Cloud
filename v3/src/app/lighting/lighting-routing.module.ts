import { LightingDevicesComponent } from './lighting-devices/lighting-devices.component';
import { LightingSchedulesComponent } from './lighting-schedules/lighting-schedules.component';
import { LightingBuildingComponent } from './lighting-building/lighting-building.component';
import { LightingOverviewComponent } from './lighting-overview/lighting-overview.component';
import { LightingComponent } from './lighting/lighting.component';
import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

const routes: Routes = [{
	path: '', component: LightingComponent, children: [
		{ path: '', pathMatch: 'full', redirectTo: 'overview' },
		{ path: 'overview', component: LightingOverviewComponent },
		{ path: 'building/:id/devices', component: LightingDevicesComponent },
		{ path: 'building/:id/schedules', component: LightingSchedulesComponent },
		{ path: 'building/:id', component: LightingBuildingComponent }
	]
}];

@NgModule({
	imports: [RouterModule.forChild(routes)],
	exports: [RouterModule]
})
export class LightingRoutingModule { }
