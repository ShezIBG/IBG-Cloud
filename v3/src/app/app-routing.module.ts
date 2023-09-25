import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { ErrorComponent } from './error/error.component';

const routes: Routes = [
	{ path: 'auth', loadChildren: 'app/auth/auth.module#AuthModule' },
	{ path: 'emergency', loadChildren: 'app/emergency/emergency.module#EmergencyModule' },
	{ path: 'sales', loadChildren: 'app/sales/sales.module#SalesModule' },
	{ path: 'settings', loadChildren: 'app/settings/settings.module#SettingsModule' },
	{ path: 'isp', loadChildren: 'app/isp/isp.module#ISPModule' },
	{ path: 'climate', loadChildren: 'app/climate/climate.module#ClimateModule' },
	{ path: 'lighting', loadChildren: 'app/lighting/lighting.module#LightingModule' },
	{ path: 'relay', loadChildren: 'app/relay/relay.module#RelayModule' },
	{ path: 'account', loadChildren: 'app/account/account.module#AccountModule' },
	{ path: 'stock', loadChildren: 'app/stock/stock.module#StockModule' },
	{ path: 'billing', loadChildren: 'app/billing/billing.module#BillingModule' },
	{ path: 'control', loadChildren: 'app/control/control.module#ControlModule' },
	{ path: 'mobile', loadChildren: 'app/mobile/mobile.module#MobileModule' },
	{ path: '', redirectTo: 'auth', pathMatch: 'full' },
	{ path: '**', component: ErrorComponent, data: { auth: false } }
];

@NgModule({
	imports: [RouterModule.forRoot(routes)],
	exports: [RouterModule]
})
export class AppRoutingModule { }
