import { ModalService } from './modal.service';
import { Component } from '@angular/core';

@Component({
	selector: 'modal-loader',
	template: '<ng-container *ngIf="componentClass"><ng-container *ngComponentOutlet="componentClass"></ng-container></ng-container>',
	providers: [ModalService]
})
export class ModalLoaderComponent {

	componentClass = null;

	constructor(private modalService: ModalService) {
		modalService.modalClosed.subscribe(() => this.componentClass = null);
	}

	open(componentCls: any, data: any) {
		if (componentCls) {
			this.modalService.data = data;
			this.componentClass = componentCls;
		}
	}

}
