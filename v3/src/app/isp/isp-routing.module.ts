import { IspAreaDetailsComponent } from './isp-area-details/isp-area-details.component';
import { IspContractEditComponent } from './isp-contract-edit/isp-contract-edit.component';
import { IspCustomerEditComponent } from './isp-customer-edit/isp-customer-edit.component';
import { IspPackageDetailsComponent } from './isp-package-details/isp-package-details.component';
import { IspPackagesComponent } from './isp-packages/isp-packages.component';
import { IspInvoiceDetailsComponent } from './isp-invoice-details/isp-invoice-details.component';
import { IspInvoicesComponent } from './isp-invoices/isp-invoices.component';
import { IspContractsComponent } from './isp-contracts/isp-contracts.component';
import { IspCustomerDetailsComponent } from './isp-customer-details/isp-customer-details.component';
import { IspCustomersComponent } from './isp-customers/isp-customers.component';
import { IspSiteDetailsComponent } from './isp-site-details/isp-site-details.component';
import { IspSitesComponent } from './isp-sites/isp-sites.component';
import { IspClientDetailsComponent } from './isp-client-details/isp-client-details.component';
import { IspClientsComponent } from './isp-clients/isp-clients.component';
import { IspOverviewComponent } from './isp-overview/isp-overview.component';
import { IspAutoSelectComponent } from './isp-auto-select/isp-auto-select.component';
import { IspComponent } from './isp/isp.component';
import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { IspOnuTypeEditComponent } from './isp-onu-type-edit/isp-onu-type-edit.component';

const routes: Routes = [
	{
		path: '', component: IspComponent, children: [
			{ path: '', pathMatch: 'full', component: IspAutoSelectComponent },
			{ path: ':isp', redirectTo: ':isp/overview' },
			{ path: ':isp/overview', component: IspOverviewComponent },
			{ path: ':isp/client', component: IspClientsComponent },
			{ path: ':isp/client/:client', component: IspClientDetailsComponent },
			{ path: ':isp/client/:client/:tab', component: IspClientDetailsComponent },
			{ path: ':isp/site', component: IspSitesComponent },
			{ path: ':isp/site/:building', component: IspSiteDetailsComponent },
			{ path: ':isp/site/:building/:tab', component: IspSiteDetailsComponent },
			{ path: ':isp/area/:area', component: IspAreaDetailsComponent },
			{ path: ':isp/customer', component: IspCustomersComponent },
			{ path: ':isp/customer/new', component: IspCustomerEditComponent },
			{ path: ':isp/customer/:customer', component: IspCustomerDetailsComponent },
			{ path: ':isp/customer/:customer/contract/new', component: IspContractEditComponent },
			{ path: ':isp/customer/:customer/contract/new/:template', component: IspContractEditComponent },
			{ path: ':isp/customer/:customer/edit', component: IspCustomerEditComponent },
			{ path: ':isp/customer/:customer/:tab', component: IspCustomerDetailsComponent },
			{ path: ':isp/contract', component: IspContractsComponent },
			{ path: ':isp/contract/new', component: IspContractEditComponent },
			{ path: ':isp/contract/:contract/edit', component: IspContractEditComponent },
			{ path: ':isp/invoice', component: IspInvoicesComponent },
			{ path: ':isp/invoice/:invoice', component: IspInvoiceDetailsComponent },
			{ path: ':isp/package', component: IspPackagesComponent },
			{ path: ':isp/package/:package', component: IspPackageDetailsComponent },
			{ path: ':isp/onu-type/new/:building', component: IspOnuTypeEditComponent },
			{ path: ':isp/onu-type/:onutype', component: IspOnuTypeEditComponent }
		]
	}
];

@NgModule({
	imports: [RouterModule.forChild(routes)],
	exports: [RouterModule]
})
export class IspRoutingModule { }
