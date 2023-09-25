import { RelaySchedulesComponent } from './relay-schedules/relay-schedules.component';
import { RelayBuildingComponent } from './relay-building/relay-building.component';
import { RelayOverviewComponent } from './relay-overview/relay-overview.component';
import { RelayComponent } from './relay/relay.component';
import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

const routes: Routes = [{
	path: '', component: RelayComponent, children: [
		{ path: '', pathMatch: 'full', redirectTo: 'overview' },
		{ path: 'overview', component: RelayOverviewComponent },
		{ path: 'building/:id/schedules', component: RelaySchedulesComponent },
		{ path: 'building/:id', component: RelayBuildingComponent }
	]
}];

@NgModule({
	imports: [RouterModule.forChild(routes)],
	exports: [RouterModule]
})
export class RelayRoutingModule { }
