import { ClimateSchedulesComponent } from './climate-schedules/climate-schedules.component';
import { ClimateDevicesComponent } from './climate-devices/climate-devices.component';
import { ClimateBuildingComponent } from './climate-building/climate-building.component';
import { ClimateOverviewComponent } from './climate-overview/climate-overview.component';
import { ClimateComponent } from './climate/climate.component';
import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

const routes: Routes = [{
	path: '', component: ClimateComponent, children: [
		{ path: '', pathMatch: 'full', redirectTo: 'overview' },
		{ path: 'overview', component: ClimateOverviewComponent },
		{ path: 'building/:id/devices', component: ClimateDevicesComponent },
		{ path: 'building/:id/schedules', component: ClimateSchedulesComponent },
		{ path: 'building/:id', component: ClimateBuildingComponent }
	]
}];

@NgModule({
	imports: [RouterModule.forChild(routes)],
	exports: [RouterModule]
})
export class ClimateRoutingModule { }
