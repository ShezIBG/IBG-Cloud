import { SettingsSmoothpowerUpdatesComponent } from './settings-smoothpower-updates/settings-smoothpower-updates.component';
import { SettingsEmailEditComponent } from './settings-email-edit/settings-email-edit.component';
import { SettingsUserRoleDefaultsEditComponent } from './settings-user-role-defaults-edit/settings-user-role-defaults-edit.component';
import { SettingsUsersComponent } from './settings-users/settings-users.component';
import { SettingsMonitorCollectorsComponent } from './settings-monitor-collectors/settings-monitor-collectors.component';
import { SettingsUserEditComponent } from './settings-user-edit/settings-user-edit.component';
import { SettingsSiteEditComponent } from './settings-site-edit/settings-site-edit.component';
import { SettingsClientEditComponent } from './settings-client-edit/settings-client-edit.component';
import { SettingsHoldingGroupEditComponent } from './settings-holding-group-edit/settings-holding-group-edit.component';
import { SettingsSystemIntegratorEditComponent } from './settings-system-integrator-edit/settings-system-integrator-edit.component';
import { SettingsServiceProviderEditComponent } from './settings-service-provider-edit/settings-service-provider-edit.component';
import { SettingsUserRoleEditComponent } from './settings-user-role-edit/settings-user-role-edit.component';
import { SettingsSiteDetailsComponent } from './settings-site-details/settings-site-details.component';
import { SettingsClientDetailsComponent } from './settings-client-details/settings-client-details.component';
import { SettingsHoldingGroupDetailsComponent } from './settings-holding-group-details/settings-holding-group-details.component';
import { SettingsSystemIntegratorDetailsComponent } from './settings-system-integrator-details/settings-system-integrator-details.component';
import { SettingsServiceProviderDetailsComponent } from './settings-service-provider-details/settings-service-provider-details.component';
import { SettingsSitesComponent } from './settings-sites/settings-sites.component';
import { SettingsClientsComponent } from './settings-clients/settings-clients.component';
import { SettingsHoldingGroupsComponent } from './settings-holding-groups/settings-holding-groups.component';
import { SettingsSystemIntegratorsComponent } from './settings-system-integrators/settings-system-integrators.component';
import { SettingsServiceProvidersComponent } from './settings-service-providers/settings-service-providers.component';
import { SettingsEticomComponent } from './settings-eticom/settings-eticom.component';
import { SettingsUserProfileComponent } from './settings-user-profile/settings-user-profile.component';
import { SettingsComponent } from './settings/settings.component';
import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { SettingsContractEditComponent } from './settings-contract-edit/settings-contract-edit.component';

const routes: Routes = [
	{
		path: '', component: SettingsComponent, children: [
			{ path: '', pathMatch: 'full', redirectTo: 'user-profile' },
			{ path: 'user-profile', component: SettingsUserProfileComponent },
			{ path: 'eticom', component: SettingsEticomComponent },
			{ path: 'eticom/:tab', component: SettingsEticomComponent },
			{ path: 'service-provider', component: SettingsServiceProvidersComponent },
			{ path: 'service-provider/new', component: SettingsServiceProviderEditComponent },
			{ path: 'service-provider/:id', component: SettingsServiceProviderDetailsComponent },
			{ path: 'service-provider/:id/edit', component: SettingsServiceProviderEditComponent },
			{ path: 'service-provider/:id/:tab', component: SettingsServiceProviderDetailsComponent },
			{ path: 'system-integrator', component: SettingsSystemIntegratorsComponent },
			{ path: 'system-integrator/new/:level/:levelId', component: SettingsSystemIntegratorEditComponent },
			{ path: 'system-integrator/:id', component: SettingsSystemIntegratorDetailsComponent },
			{ path: 'system-integrator/:id/edit', component: SettingsSystemIntegratorEditComponent },
			{ path: 'system-integrator/:id/:tab', component: SettingsSystemIntegratorDetailsComponent },
			{ path: 'holding-group', component: SettingsHoldingGroupsComponent },
			{ path: 'holding-group/new/:level/:levelId', component: SettingsHoldingGroupEditComponent },
			{ path: 'holding-group/:id', component: SettingsHoldingGroupDetailsComponent },
			{ path: 'holding-group/:id/edit', component: SettingsHoldingGroupEditComponent },
			{ path: 'holding-group/:id/:tab', component: SettingsHoldingGroupDetailsComponent },
			{ path: 'client', component: SettingsClientsComponent },
			{ path: 'client/new/:level/:levelId', component: SettingsClientEditComponent },
			{ path: 'client/:id', component: SettingsClientDetailsComponent },
			{ path: 'client/:id/edit', component: SettingsClientEditComponent },
			{ path: 'client/:id/:tab', component: SettingsClientDetailsComponent },
			{ path: 'site', component: SettingsSitesComponent },
			{ path: 'site/new/:level/:levelId', component: SettingsSiteEditComponent },
			{ path: 'site/:id', component: SettingsSiteDetailsComponent },
			{ path: 'site/:id/edit', component: SettingsSiteEditComponent },
			{ path: 'site/:id/:tab', component: SettingsSiteDetailsComponent },
			{ path: 'user-role/defaults', component: SettingsUserRoleDefaultsEditComponent },
			{ path: 'user-role/:id', component: SettingsUserRoleEditComponent },
			{ path: 'user-role/:id/:level/:levelId', component: SettingsUserRoleEditComponent },
			{ path: 'user', component: SettingsUsersComponent },
			{ path: 'user/new', component: SettingsUserEditComponent },
			{ path: 'user/new/:level/:levelId', component: SettingsUserEditComponent },
			{ path: 'user/:id', component: SettingsUserEditComponent },
			{ path: 'user/:id/:level/:levelId', component: SettingsUserEditComponent },
			{ path: 'monitor-collectors', component: SettingsMonitorCollectorsComponent },
			{ path: 'monitor-collectors/:tab', component: SettingsMonitorCollectorsComponent },
			{ path: 'email/:level/:levelId/:template', component: SettingsEmailEditComponent },
			{ path: 'contract/:level/:levelId/:contract', component: SettingsContractEditComponent },
			{ path: 'smoothpower-updates', component: SettingsSmoothpowerUpdatesComponent }
		]
	}
];

@NgModule({
	imports: [RouterModule.forChild(routes)],
	exports: [RouterModule]
})
export class SettingsRoutingModule { }
