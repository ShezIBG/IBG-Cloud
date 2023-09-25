import { AppService } from './../app.service';
import { ModalEvent } from './../modal/modal.component';
import { ModalService } from './../modal/modal.service';
import { Component } from '@angular/core';

@Component({
	selector: 'app-commit-result',
	templateUrl: './commit-result.component.html'
})
export class CommitResultComponent {

	result: any;
	buttons = [];

	constructor(public app: AppService, public modalService: ModalService) {
		this.result = modalService.data.result;

		if (this.result.status === 'OK') {
			this.buttons = ['0|+Reload'];
		} else {
			if (this.result.severe) {
				this.buttons = [];
			} else {
				this.buttons = ['1|Close'];
			}
		}
	}

	modalHandler(event: ModalEvent) {
		if (event.type === 'button') {
			switch (event.data.id) {
				case 0:
					this.app.forcedReload = true;
					window.location.reload(true);
					break;
				case 1:
					event.modal.close();
					break;
			}
		}
	}

}
