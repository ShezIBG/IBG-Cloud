import { AppService } from './../../app.service';
import { ModalService } from './../../shared/modal/modal.service';
import { ApiService } from './../../api.service';
import { ModalComponent } from './../../shared/modal/modal.component';
import { Component, ViewChild } from '@angular/core';

@Component({
	selector: 'app-sales-project-stage-history-modal',
	templateUrl: './sales-project-stage-history-modal.component.html'
})
export class SalesProjectStageHistoryModalComponent {

	@ViewChild(ModalComponent) modal: ModalComponent;

	projectId: any = null;
	history = null;

	constructor(
		private app: AppService,
		private api: ApiService,
		private modalService: ModalService
	) {
		this.projectId = this.modalService.data;
		this.api.sales.getProjectStageHistory(modalService.data, response => {
			this.history = response.data;
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	modalHandler(event) {
		this.modal.close();
	}

}
