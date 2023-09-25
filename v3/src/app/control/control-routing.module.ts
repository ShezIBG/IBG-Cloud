import { ControlDevicesComponent } from './control-devices/control-devices.component';
import { ControlSchedulesComponent } from './control-schedules/control-schedules.component';
import { ControlBuildingComponent } from './control-building/control-building.component';
import { ControlOverviewComponent } from './control-overview/control-overview.component';
import { ControlComponent } from './control/control.component';
import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

const routes: Routes = [{
	path: '', component: ControlComponent, children: [
		{ path: '', pathMatch: 'full', redirectTo: 'overview' },
		{ path: 'overview', component: ControlOverviewComponent },
		{ path: 'building/:id/devices', component: ControlDevicesComponent },
		{ path: 'building/:id/schedules', component: ControlSchedulesComponent },
		{ path: 'building/:id', component: ControlBuildingComponent }
	]
}];

@NgModule({
	imports: [RouterModule.forChild(routes)],
	exports: [RouterModule]
})
export class ControlRoutingModule { }
