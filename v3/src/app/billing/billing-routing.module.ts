import { BillingCustomersInArrearsComponent } from './billing-customers-in-arrears/billing-customers-in-arrears.component';
import { BillingInvoiceEntityEditComponent } from './billing-invoice-entity-edit/billing-invoice-entity-edit.component';
import { BillingInvoiceEntitiesComponent } from './billing-invoice-entities/billing-invoice-entities.component';
import { BillingSystemIntegratorDetailsComponent } from './billing-system-integrator-details/billing-system-integrator-details.component';
import { BillingSystemIntegratorsComponent } from './billing-system-integrators/billing-system-integrators.component';
import { BillingAreaDetailsComponent } from './billing-area-details/billing-area-details.component';
import { BillingContractEditComponent } from './billing-contract-edit/billing-contract-edit.component';
import { BillingCustomerEditComponent } from './billing-customer-edit/billing-customer-edit.component';
import { BillingInvoiceDetailsComponent } from './billing-invoice-details/billing-invoice-details.component';
import { BillingInvoicesComponent } from './billing-invoices/billing-invoices.component';
import { BillingContractsComponent } from './billing-contracts/billing-contracts.component';
import { BillingCustomerDetailsComponent } from './billing-customer-details/billing-customer-details.component';
import { BillingCustomersComponent } from './billing-customers/billing-customers.component';
import { BillingSiteDetailsComponent } from './billing-site-details/billing-site-details.component';
import { BillingSitesComponent } from './billing-sites/billing-sites.component';
import { BillingClientDetailsComponent } from './billing-client-details/billing-client-details.component';
import { BillingClientsComponent } from './billing-clients/billing-clients.component';
import { BillingOverviewComponent } from './billing-overview/billing-overview.component';
import { BillingAutoSelectComponent } from './billing-auto-select/billing-auto-select.component';
import { BillingComponent } from './billing/billing.component';
import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

const routes: Routes = [
	{
		path: '', component: BillingComponent, children: [
			{ path: '', pathMatch: 'full', component: BillingAutoSelectComponent },
			{ path: ':owner', redirectTo: ':owner/overview' },
			{ path: ':owner/overview', component: BillingOverviewComponent },
			{ path: ':owner/system-integrator', component: BillingSystemIntegratorsComponent },
			{ path: ':owner/system-integrator/:si', component: BillingSystemIntegratorDetailsComponent },
			{ path: ':owner/system-integrator/:si/contract/new', component: BillingContractEditComponent },
			{ path: ':owner/system-integrator/:si/contract/new/:template', component: BillingContractEditComponent },
			{ path: ':owner/system-integrator/:si/:tab', component: BillingSystemIntegratorDetailsComponent },
			{ path: ':owner/client', component: BillingClientsComponent },
			{ path: ':owner/client/:client', component: BillingClientDetailsComponent },
			{ path: ':owner/client/:client/contract/new', component: BillingContractEditComponent },
			{ path: ':owner/client/:client/contract/new/:template', component: BillingContractEditComponent },
			{ path: ':owner/client/:client/:tab', component: BillingClientDetailsComponent },
			{ path: ':owner/site', component: BillingSitesComponent },
			{ path: ':owner/site/:building', component: BillingSiteDetailsComponent },
			{ path: ':owner/site/:building/:tab', component: BillingSiteDetailsComponent },
			{ path: ':owner/area/:area', component: BillingAreaDetailsComponent },
			{ path: ':owner/customer', component: BillingCustomersComponent },
			{ path: ':owner/customer/in-arrears', component: BillingCustomersInArrearsComponent },
			{ path: ':owner/customer/new', component: BillingCustomerEditComponent },
			{ path: ':owner/customer/:customer', component: BillingCustomerDetailsComponent },
			{ path: ':owner/customer/:customer/contract/new', component: BillingContractEditComponent },
			{ path: ':owner/customer/:customer/contract/new/:template', component: BillingContractEditComponent },
			{ path: ':owner/customer/:customer/edit', component: BillingCustomerEditComponent },
			{ path: ':owner/customer/:customer/:tab', component: BillingCustomerDetailsComponent },
			{ path: ':owner/contract', component: BillingContractsComponent },
			{ path: ':owner/contract/new', component: BillingContractEditComponent },
			{ path: ':owner/contract/:contract/edit', component: BillingContractEditComponent },
			{ path: ':owner/invoice', component: BillingInvoicesComponent },
			{ path: ':owner/invoice/:invoice', component: BillingInvoiceDetailsComponent },
			{ path: ':owner/invoice-entity', component: BillingInvoiceEntitiesComponent },
			{ path: ':owner/invoice-entity/new', component: BillingInvoiceEntityEditComponent },
			{ path: ':owner/invoice-entity/:entity', component: BillingInvoiceEntityEditComponent }
		]
	}
];

@NgModule({
	imports: [RouterModule.forChild(routes)],
	exports: [RouterModule]
})
export class BillingRoutingModule { }
