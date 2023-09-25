import { Component, Input } from '@angular/core';

@Component({
	selector: 'app-sales-project-stage-badge',
	template: `
		<span *ngIf="stage === 'cancelled'" class="badge badge-danger">Cancelled</span>
		<span *ngIf="stage === 'lead'" class="badge badge-warning">Lead</span>
		<span *ngIf="stage === 'survey'" class="badge badge-info">Survey</span>
		<span *ngIf="stage === 'quote'" class="badge badge-info">Quote</span>
		<span *ngIf="stage === 'build'" class="badge badge-inverse">Build</span>
		<span *ngIf="stage === 'install'" class="badge badge-inverse">Install</span>
		<span *ngIf="stage === 'complete'" class="badge badge-success">Complete</span>
	`
})
export class SalesProjectStageBadgeComponent {

	@Input() stage;

}
