import { BillingService } from './billing.service';
import { FormsModule } from '@angular/forms';
import { SharedModule } from './../shared/shared.module';
import { CalendarModule } from 'primeng/primeng';
import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { BillingRoutingModule } from './billing-routing.module';
import { BillingComponent } from './billing/billing.component';
import { BillingAutoSelectComponent } from './billing-auto-select/billing-auto-select.component';
import { BillingOverviewComponent } from './billing-overview/billing-overview.component';
import { BillingClientsComponent } from './billing-clients/billing-clients.component';
import { BillingSitesComponent } from './billing-sites/billing-sites.component';
import { BillingClientDetailsComponent } from './billing-client-details/billing-client-details.component';
import { BillingSiteDetailsComponent } from './billing-site-details/billing-site-details.component';
import { BillingAreasComponent } from './billing-areas/billing-areas.component';
import { BillingCustomersComponent } from './billing-customers/billing-customers.component';
import { BillingCustomerDetailsComponent } from './billing-customer-details/billing-customer-details.component';
import { BillingContractsComponent } from './billing-contracts/billing-contracts.component';
import { BillingInvoicesComponent } from './billing-invoices/billing-invoices.component';
import { BillingInvoiceDetailsComponent } from './billing-invoice-details/billing-invoice-details.component';
import { BillingCustomerEditComponent } from './billing-customer-edit/billing-customer-edit.component';
import { BillingContractEditComponent } from './billing-contract-edit/billing-contract-edit.component';
import { BillingAreaDetailsComponent } from './billing-area-details/billing-area-details.component';
import { BillingInvoiceCounterEditModalComponent } from './billing-invoice-counter-edit-modal/billing-invoice-counter-edit-modal.component';
import { BillingCustomerEmailModalComponent } from './billing-customer-email-modal/billing-customer-email-modal.component';
import { BillingInvoiceNoEditModalComponent } from './billing-invoice-no-edit-modal/billing-invoice-no-edit-modal.component';
import { BillingSystemIntegratorsComponent } from './billing-system-integrators/billing-system-integrators.component';
import { BillingSystemIntegratorDetailsComponent } from './billing-system-integrator-details/billing-system-integrator-details.component';
import { BillingInvoiceEntitiesComponent } from './billing-invoice-entities/billing-invoice-entities.component';
import { BillingInvoiceEntityEditComponent } from './billing-invoice-entity-edit/billing-invoice-entity-edit.component';
import { BillingCustomersInArrearsComponent } from './billing-customers-in-arrears/billing-customers-in-arrears.component';

@NgModule({
	imports: [
		CalendarModule,
		CommonModule,
		SharedModule,
		BillingRoutingModule,
		FormsModule
	],
	declarations: [
		BillingComponent,
		BillingAutoSelectComponent,
		BillingOverviewComponent,
		BillingClientsComponent,
		BillingSitesComponent,
		BillingClientDetailsComponent,
		BillingSiteDetailsComponent,
		BillingAreasComponent,
		BillingCustomersComponent,
		BillingCustomerDetailsComponent,
		BillingContractsComponent,
		BillingInvoicesComponent,
		BillingInvoiceDetailsComponent,
		BillingCustomerEditComponent,
		BillingContractEditComponent,
		BillingAreaDetailsComponent,
		BillingInvoiceCounterEditModalComponent,
		BillingCustomerEmailModalComponent,
		BillingInvoiceNoEditModalComponent,
		BillingSystemIntegratorsComponent,
		BillingSystemIntegratorDetailsComponent,
		BillingInvoiceEntitiesComponent,
		BillingInvoiceEntityEditComponent,
		BillingCustomersInArrearsComponent
	],
	providers: [
		BillingService
	],
	entryComponents: [
		BillingInvoiceCounterEditModalComponent,
		BillingCustomerEmailModalComponent,
		BillingInvoiceNoEditModalComponent
	]
})
export class BillingModule { }
