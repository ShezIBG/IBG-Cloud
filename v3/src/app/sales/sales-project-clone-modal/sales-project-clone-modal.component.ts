import { ModalService } from './../../shared/modal/modal.service';
import { ModalComponent } from './../../shared/modal/modal.component';
import { Component, ViewChild } from '@angular/core';

export interface ProjectCloneFlags {
	systems: boolean;
	structure: boolean;
	products: boolean;
	proposal: boolean;
}

export interface ProjectCloneOptions {
	id: string,
	description: string,
	clone: ProjectCloneFlags
};

@Component({
	selector: 'app-sales-project-clone-modal',
	templateUrl: './sales-project-clone-modal.component.html'
})
export class SalesProjectCloneModalComponent {

	@ViewChild(ModalComponent) modal: ModalComponent;

	get clone_systems() { return this.options.clone.systems; }
	set clone_systems(value) {
		this.options.clone.systems = value;
		if (!value) this.options.clone.products = false;
	}

	get clone_structure() { return this.options.clone.structure; }
	set clone_structure(value) {
		this.options.clone.structure = value;
		if (!value) this.options.clone.products = false;
	}

	get clone_products() { return this.options.clone.products; }
	set clone_products(value) { this.options.clone.products = value; }

	get clone_proposal() { return this.options.clone.proposal; }
	set clone_proposal(value) { this.options.clone.proposal = value; }

	options: ProjectCloneOptions = null;

	constructor(private modalService: ModalService) {
		this.options = this.modalService.data;
	}

	modalHandler(event) {
		if (event.type === 'button' && event.data && event.data.id === 1) {
			this.modal.close(this.options);
		} else {
			this.modal.close();
		}
	}

}
