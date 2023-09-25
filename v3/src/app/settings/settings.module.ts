import { EditorModule } from '@tinymce/tinymce-angular';
import { CalendarModule } from 'primeng/primeng';
import { FormsModule } from '@angular/forms';
import { SettingsService } from './settings.service';
import { SharedModule } from './../shared/shared.module';
import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { SettingsRoutingModule } from './settings-routing.module';
import { SettingsComponent } from './settings/settings.component';
import { SettingsUserProfileComponent } from './settings-user-profile/settings-user-profile.component';
import { SettingsEticomComponent } from './settings-eticom/settings-eticom.component';
import { SettingsServiceProvidersComponent } from './settings-service-providers/settings-service-providers.component';
import { SettingsSystemIntegratorsComponent } from './settings-system-integrators/settings-system-integrators.component';
import { SettingsHoldingGroupsComponent } from './settings-holding-groups/settings-holding-groups.component';
import { SettingsClientsComponent } from './settings-clients/settings-clients.component';
import { SettingsSitesComponent } from './settings-sites/settings-sites.component';
import { SettingsUsersComponent } from './settings-users/settings-users.component';
import { SettingsUserRolesComponent } from './settings-user-roles/settings-user-roles.component';
import { SettingsServiceProviderDetailsComponent } from './settings-service-provider-details/settings-service-provider-details.component';
import { SettingsSystemIntegratorDetailsComponent } from './settings-system-integrator-details/settings-system-integrator-details.component';
import { SettingsHoldingGroupDetailsComponent } from './settings-holding-group-details/settings-holding-group-details.component';
import { SettingsClientDetailsComponent } from './settings-client-details/settings-client-details.component';
import { SettingsSiteDetailsComponent } from './settings-site-details/settings-site-details.component';
import { SettingsServiceProviderEditComponent } from './settings-service-provider-edit/settings-service-provider-edit.component';
import { SettingsSystemIntegratorEditComponent } from './settings-system-integrator-edit/settings-system-integrator-edit.component';
import { SettingsHoldingGroupEditComponent } from './settings-holding-group-edit/settings-holding-group-edit.component';
import { SettingsClientEditComponent } from './settings-client-edit/settings-client-edit.component';
import { SettingsUserRoleEditComponent } from './settings-user-role-edit/settings-user-role-edit.component';
import { SettingsPermissionsComponent } from './settings-permissions/settings-permissions.component';
import { SettingsSiteEditComponent } from './settings-site-edit/settings-site-edit.component';
import { SettingsUserEditComponent } from './settings-user-edit/settings-user-edit.component';
import { SettingsSelectLevelModalComponent } from './settings-select-level-modal/settings-select-level-modal.component';
import { SettingsMonitorCollectorsComponent } from './settings-monitor-collectors/settings-monitor-collectors.component';
import { SettingsMonitorCollectorModalComponent } from './settings-monitor-collector-modal/settings-monitor-collector-modal.component';
import { SettingsUserRoleDefaultsEditComponent } from './settings-user-role-defaults-edit/settings-user-role-defaults-edit.component';
import { SettingsPaymentGatewaysComponent } from './settings-payment-gateways/settings-payment-gateways.component';
import { SettingsPaymentGatewayEditModalComponent } from './settings-payment-gateway-edit-modal/settings-payment-gateway-edit-modal.component';
import { SettingsEmailsComponent } from './settings-emails/settings-emails.component';
import { SettingsEmailEditComponent } from './settings-email-edit/settings-email-edit.component';
import { SettingsSmoothpowerUpdatesComponent } from './settings-smoothpower-updates/settings-smoothpower-updates.component';
import { SettingsContractsComponent } from './settings-contracts/settings-contracts.component';
import { SettingsContractEditComponent } from './settings-contract-edit/settings-contract-edit.component';

@NgModule({
	imports: [
		CalendarModule,
		CommonModule,
		SharedModule,
		SettingsRoutingModule,
		FormsModule,
		EditorModule
	],
	declarations: [
		SettingsComponent,
		SettingsUserProfileComponent,
		SettingsEticomComponent,
		SettingsServiceProvidersComponent,
		SettingsSystemIntegratorsComponent,
		SettingsHoldingGroupsComponent,
		SettingsClientsComponent,
		SettingsSitesComponent,
		SettingsUsersComponent,
		SettingsUserRolesComponent,
		SettingsServiceProviderDetailsComponent,
		SettingsSystemIntegratorDetailsComponent,
		SettingsHoldingGroupDetailsComponent,
		SettingsClientDetailsComponent,
		SettingsSiteDetailsComponent,
		SettingsServiceProviderEditComponent,
		SettingsSystemIntegratorEditComponent,
		SettingsHoldingGroupEditComponent,
		SettingsClientEditComponent,
		SettingsUserRoleEditComponent,
		SettingsPermissionsComponent,
		SettingsSiteEditComponent,
		SettingsUserEditComponent,
		SettingsSelectLevelModalComponent,
		SettingsMonitorCollectorsComponent,
		SettingsMonitorCollectorModalComponent,
		SettingsUserRoleDefaultsEditComponent,
		SettingsPaymentGatewaysComponent,
		SettingsPaymentGatewayEditModalComponent,
		SettingsEmailsComponent,
		SettingsEmailEditComponent,
		SettingsSmoothpowerUpdatesComponent,
		SettingsContractsComponent,
		SettingsContractEditComponent
	],
	entryComponents: [
		SettingsSelectLevelModalComponent,
		SettingsMonitorCollectorModalComponent,
		SettingsPaymentGatewayEditModalComponent
	],
	providers: [
		SettingsService
	]
})
export class SettingsModule { }
