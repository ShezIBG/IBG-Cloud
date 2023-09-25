import { Component, NgModuleRef } from '@angular/core';
import { ModalService } from './modal.service';

@Component({
	selector: 'app-modal-loader',
	template: '<ng-container *ngIf="componentClass"><ng-container *ngComponentOutlet="componentClass;injector:injector;"></ng-container></ng-container>'
})
export class ModalLoaderComponent {
	componentClass = null;
	injector = null;

	constructor(
		public modalService: ModalService,
		private sharedModuleRef: NgModuleRef<any>
	) {
		modalService.modalClosed.subscribe(() => this.componentClass = null);
	}

	open(componentCls: any, moduleRef: NgModuleRef<any>, data: any) {
		if (componentCls) {
			this.modalService.data = data;
			this.componentClass = componentCls;
			if (moduleRef) {
				this.injector = moduleRef.injector;
			} else {
				this.injector = this.sharedModuleRef.injector;
			}
		}
	}

}
