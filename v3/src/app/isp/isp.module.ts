import { IspService } from './isp.service';
import { FormsModule } from '@angular/forms';
import { SharedModule } from './../shared/shared.module';
import { CalendarModule } from 'primeng/primeng';
import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { IspRoutingModule } from './isp-routing.module';
import { IspComponent } from './isp/isp.component';
import { IspAutoSelectComponent } from './isp-auto-select/isp-auto-select.component';
import { IspOverviewComponent } from './isp-overview/isp-overview.component';
import { IspClientsComponent } from './isp-clients/isp-clients.component';
import { IspSitesComponent } from './isp-sites/isp-sites.component';
import { IspClientDetailsComponent } from './isp-client-details/isp-client-details.component';
import { IspSiteDetailsComponent } from './isp-site-details/isp-site-details.component';
import { IspAreasComponent } from './isp-areas/isp-areas.component';
import { IspCustomersComponent } from './isp-customers/isp-customers.component';
import { IspCustomerDetailsComponent } from './isp-customer-details/isp-customer-details.component';
import { IspContractsComponent } from './isp-contracts/isp-contracts.component';
import { IspInvoicesComponent } from './isp-invoices/isp-invoices.component';
import { IspInvoiceDetailsComponent } from './isp-invoice-details/isp-invoice-details.component';
import { IspPackagesComponent } from './isp-packages/isp-packages.component';
import { IspPackageDetailsComponent } from './isp-package-details/isp-package-details.component';
import { IspCustomerEditComponent } from './isp-customer-edit/isp-customer-edit.component';
import { IspContractEditComponent } from './isp-contract-edit/isp-contract-edit.component';
import { IspAreaDetailsComponent } from './isp-area-details/isp-area-details.component';
import { IspInvoiceCounterEditModalComponent } from './isp-invoice-counter-edit-modal/isp-invoice-counter-edit-modal.component';
import { IspCustomerEmailModalComponent } from './isp-customer-email-modal/isp-customer-email-modal.component';
import { IspInvoiceNoEditModalComponent } from './isp-invoice-no-edit-modal/isp-invoice-no-edit-modal.component';
import { IspAreaNoteModalComponent } from './isp-area-note-modal/isp-area-note-modal.component';
import { IspOnuTypesComponent } from './isp-onu-types/isp-onu-types.component';
import { IspOnuTypeEditComponent } from './isp-onu-type-edit/isp-onu-type-edit.component';
import { EditorModule } from '@tinymce/tinymce-angular';

@NgModule({
	imports: [
		CalendarModule,
		CommonModule,
		SharedModule,
		IspRoutingModule,
		FormsModule,
		EditorModule
	],
	declarations: [
		IspComponent,
		IspAutoSelectComponent,
		IspOverviewComponent,
		IspClientsComponent,
		IspSitesComponent,
		IspClientDetailsComponent,
		IspSiteDetailsComponent,
		IspAreasComponent,
		IspCustomersComponent,
		IspCustomerDetailsComponent,
		IspContractsComponent,
		IspInvoicesComponent,
		IspInvoiceDetailsComponent,
		IspPackagesComponent,
		IspPackageDetailsComponent,
		IspCustomerEditComponent,
		IspContractEditComponent,
		IspAreaDetailsComponent,
		IspInvoiceCounterEditModalComponent,
		IspCustomerEmailModalComponent,
		IspInvoiceNoEditModalComponent,
		IspAreaNoteModalComponent,
		IspOnuTypesComponent,
		IspOnuTypeEditComponent
	],
	providers: [
		IspService
	],
	entryComponents: [
		IspInvoiceCounterEditModalComponent,
		IspCustomerEmailModalComponent,
		IspInvoiceNoEditModalComponent,
		IspAreaNoteModalComponent
	]
})
export class ISPModule { }
