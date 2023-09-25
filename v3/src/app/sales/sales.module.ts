import { CalendarModule } from 'primeng/primeng';
import { EditorModule } from '@tinymce/tinymce-angular';
import { FormsModule } from '@angular/forms';
import { SharedModule } from './../shared/shared.module';
import { SalesService } from './sales.service';
import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DragDropModule } from '@angular/cdk/drag-drop';

import { SalesRoutingModule } from './sales-routing.module';
import { SalesComponent } from './sales/sales.component';
import { SalesOverviewComponent } from './sales-overview/sales-overview.component';
import { SalesCustomersComponent } from './sales-customers/sales-customers.component';
import { SalesProjectsComponent } from './sales-projects/sales-projects.component';
import { SalesProjectSummaryComponent } from './sales-project-summary/sales-project-summary.component';
import { SalesCustomerEditComponent } from './sales-customer-edit/sales-customer-edit.component';
import { SalesProjectEditComponent } from './sales-project-edit/sales-project-edit.component';
import { SalesProjectEditorComponent } from './sales-project-editor/sales-project-editor.component';
import { SalesProjectStageBadgeComponent } from './sales-project-stage-badge/sales-project-stage-badge.component';
import { SalesProjectStageControlComponent } from './sales-project-stage-control/sales-project-stage-control.component';
import { SalesProjectAddSystemModalComponent } from './sales-project-add-system-modal/sales-project-add-system-modal.component';
import { SalesProjectEditSystemModalComponent } from './sales-project-edit-system-modal/sales-project-edit-system-modal.component';
import { SalesProjectEditStructureModalComponent } from './sales-project-edit-structure-modal/sales-project-edit-structure-modal.component';
import { SalesProjectProductAddModalComponent } from './sales-project-product-add-modal/sales-project-product-add-modal.component';
import { SalesProjectLineEditModalComponent } from './sales-project-line-edit-modal/sales-project-line-edit-modal.component';
import { SalesProjectCostSummaryComponent } from './sales-project-cost-summary/sales-project-cost-summary.component';
import { SalesProjectPORequestComponent } from './sales-project-po-request/sales-project-po-request.component';
import { SalesProjectStageHistoryModalComponent } from './sales-project-stage-history-modal/sales-project-stage-history-modal.component';
import { SalesProjectItemisedQuotationComponent } from './sales-project-itemised-quotation/sales-project-itemised-quotation.component';
import { SalesProjectProposalComponent } from './sales-project-proposal/sales-project-proposal.component';
import { SalesProjectCloneModalComponent } from './sales-project-clone-modal/sales-project-clone-modal.component';
import { SalesProjectPriceAdjustmentsComponent } from './sales-project-price-adjustments/sales-project-price-adjustments.component';
import { SalesProjectSystemsComponent } from './sales-project-systems/sales-project-systems.component';
import { SalesProjectSystemEditComponent } from './sales-project-system-edit/sales-project-system-edit.component';
import { SalesProjectSystemModuleEditComponent } from './sales-project-system-module-edit/sales-project-system-module-edit.component';

@NgModule({
	imports: [
		CalendarModule,
		CommonModule,
		SharedModule,
		SalesRoutingModule,
		FormsModule,
		EditorModule,
		DragDropModule
	],
	declarations: [
		SalesComponent,
		SalesOverviewComponent,
		SalesCustomersComponent,
		SalesProjectsComponent,
		SalesProjectSummaryComponent,
		SalesCustomerEditComponent,
		SalesProjectEditComponent,
		SalesProjectEditorComponent,
		SalesProjectStageBadgeComponent,
		SalesProjectStageControlComponent,
		SalesProjectAddSystemModalComponent,
		SalesProjectEditSystemModalComponent,
		SalesProjectEditStructureModalComponent,
		SalesProjectProductAddModalComponent,
		SalesProjectLineEditModalComponent,
		SalesProjectCostSummaryComponent,
		SalesProjectPORequestComponent,
		SalesProjectStageHistoryModalComponent,
		SalesProjectItemisedQuotationComponent,
		SalesProjectProposalComponent,
		SalesProjectCloneModalComponent,
		SalesProjectPriceAdjustmentsComponent,
		SalesProjectSystemsComponent,
		SalesProjectSystemEditComponent,
		SalesProjectSystemModuleEditComponent,
	],
	providers: [
		SalesService
	],
	entryComponents: [
		SalesProjectAddSystemModalComponent,
		SalesProjectEditSystemModalComponent,
		SalesProjectEditStructureModalComponent,
		SalesProjectProductAddModalComponent,
		SalesProjectLineEditModalComponent,
		SalesProjectStageHistoryModalComponent,
		SalesProjectCloneModalComponent
	]
})
export class SalesModule { }
