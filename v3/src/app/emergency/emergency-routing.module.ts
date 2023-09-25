import { EmergencyFaultsComponent } from './emergency-faults/emergency-faults.component';
import { EmergencyLightsComponent } from './emergency-lights/emergency-lights.component';
import { EmergencyGroupsComponent } from './emergency-groups/emergency-groups.component';
import { EmergencyComponent } from './emergency/emergency.component';
import { EmergencyBuildingComponent } from './emergency-building/emergency-building.component';
import { EmergencyOverviewComponent } from './emergency-overview/emergency-overview.component';
import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

const routes: Routes = [{
	path: '', component: EmergencyComponent, children: [
		{ path: '', pathMatch: 'full', redirectTo: 'overview' },
		{ path: 'overview', component: EmergencyOverviewComponent },
		{ path: 'building/:id', component: EmergencyBuildingComponent },
		{ path: 'building/:id/groups', component: EmergencyGroupsComponent },
		{ path: 'building/:id/lights', component: EmergencyLightsComponent },
		{ path: 'building/:id/faults', component: EmergencyFaultsComponent }
	]
}];

@NgModule({
	imports: [RouterModule.forChild(routes)],
	exports: [RouterModule]
})
export class EmergencyRoutingModule { }
