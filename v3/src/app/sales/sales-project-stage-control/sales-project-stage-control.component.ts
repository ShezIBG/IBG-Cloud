import { SalesProjectStageHistoryModalComponent } from './../sales-project-stage-history-modal/sales-project-stage-history-modal.component';
import { AppService } from './../../app.service';
import { Component, Input, Output, EventEmitter, NgModuleRef } from '@angular/core';

@Component({
	selector: 'app-sales-project-stage-control',
	templateUrl: './sales-project-stage-control.component.html'
})
export class SalesProjectStageControlComponent {

	@Input() project;

	@Output() stageUpdated = new EventEmitter<string>();

	constructor(
		private app: AppService,
		private moduleRef: NgModuleRef<any>
	) { }

	updateStage(newStage) {
		if (this.project.stage === newStage) return;

		this.project.stage = newStage;
		this.stageUpdated.emit(newStage);
	}

	showStageHistory() {
		if (this.project.id && this.project.id !== 'new') {
			this.app.modal.open(SalesProjectStageHistoryModalComponent, this.moduleRef, this.project.id);
		}
	}

}
