import { SalesProjectSystemEditComponent } from './sales-project-system-edit/sales-project-system-edit.component';
import { SalesProjectSystemModuleEditComponent } from './sales-project-system-module-edit/sales-project-system-module-edit.component';
import { SalesProjectSystemsComponent } from './sales-project-systems/sales-project-systems.component';
import { SalesProjectPriceAdjustmentsComponent } from './sales-project-price-adjustments/sales-project-price-adjustments.component';
import { SalesProjectProposalComponent } from './sales-project-proposal/sales-project-proposal.component';
import { SalesProjectItemisedQuotationComponent } from './sales-project-itemised-quotation/sales-project-itemised-quotation.component';
import { SalesProjectPORequestComponent } from './sales-project-po-request/sales-project-po-request.component';
import { SalesProjectCostSummaryComponent } from './sales-project-cost-summary/sales-project-cost-summary.component';
import { SalesProjectEditorComponent } from './sales-project-editor/sales-project-editor.component';
import { SalesProjectEditComponent } from './sales-project-edit/sales-project-edit.component';
import { SalesCustomerEditComponent } from './sales-customer-edit/sales-customer-edit.component';
import { SalesProjectSummaryComponent } from './sales-project-summary/sales-project-summary.component';
import { SalesProjectsComponent } from './sales-projects/sales-projects.component';
import { SalesCustomersComponent } from './sales-customers/sales-customers.component';
import { SalesOverviewComponent } from './sales-overview/sales-overview.component';
import { SalesComponent } from './sales/sales.component';
import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

const routes: Routes = [
	{
		path: '', component: SalesComponent, data: { showOwner: true }, children: [
			{ path: '', pathMatch: 'full', redirectTo: 'overview' },
			{ path: 'overview', component: SalesOverviewComponent, data: { changeOwner: true } },
			{ path: 'customer', component: SalesCustomersComponent, data: { changeOwner: true } },
			{ path: 'customer/:customerId', component: SalesCustomerEditComponent },
			{ path: 'customer/new/:owner', component: SalesCustomerEditComponent },
			{ path: 'project', component: SalesProjectsComponent, data: { changeOwner: true } },
			{ path: 'project/all', redirectTo: 'project' },
			{ path: 'project/new/:owner', component: SalesProjectEditComponent },
			{ path: 'project/:projectId', redirectTo: 'project/:projectId/summary' },
			{ path: 'project/:projectId/summary', component: SalesProjectSummaryComponent },
			{ path: 'project/:projectId/details', component: SalesProjectEditComponent },
			{ path: 'project/:projectId/editor', component: SalesProjectEditorComponent },
			{ path: 'project/:projectId/price-adjustments', component: SalesProjectPriceAdjustmentsComponent },
			{ path: 'project/:projectId/cost-summary', component: SalesProjectCostSummaryComponent },
			{ path: 'project/:projectId/po-request', component: SalesProjectPORequestComponent },
			{ path: 'project/:projectId/itemised-quotation', component: SalesProjectItemisedQuotationComponent },
			{ path: 'project/:projectId/proposal', component: SalesProjectProposalComponent },
			{ path: 'project-system', component: SalesProjectSystemsComponent, data: { changeOwner: true } },
			{ path: 'project-system/module/:id', component: SalesProjectSystemModuleEditComponent },
			{ path: 'project-system/module/:id/:owner', component: SalesProjectSystemModuleEditComponent },
			{ path: 'project-system/:id', component: SalesProjectSystemEditComponent },
			{ path: 'project-system/:id/:moduleId/:owner', component: SalesProjectSystemEditComponent },
		]
	}
];

@NgModule({
	imports: [RouterModule.forChild(routes)],
	exports: [RouterModule]
})
export class SalesRoutingModule { }
