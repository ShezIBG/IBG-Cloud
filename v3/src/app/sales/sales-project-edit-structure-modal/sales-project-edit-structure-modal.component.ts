import { AppService } from './../../app.service';
import { ModalComponent } from './../../shared/modal/modal.component';
import { ModalService } from './../../shared/modal/modal.service';
import { ApiService } from './../../api.service';
import { Component, ViewChild } from '@angular/core';

@Component({
	selector: 'app-sales-project-edit-structure-modal',
	templateUrl: './sales-project-edit-structure-modal.component.html'
})
export class SalesProjectEditStructureModalComponent {

	@ViewChild(ModalComponent) modal;

	data;
	typeDescription = '';
	parentDescription = '';

	isNew = false;
	title = '';
	buttons = [];

	constructor(
		private app: AppService,
		private api: ApiService,
		private modalService: ModalService
	) {
		this.data = this.modalService.data;
		this.isNew = this.data.details.id === 'new';

		switch (this.data.details.type) {
			case 'floor':
				this.typeDescription = 'Block';
				break;

			case 'area':
				this.typeDescription = 'Area';
				this.parentDescription = 'Block';
				break;
		}

		this.title = this.isNew ? 'New ' + this.typeDescription.toLowerCase() : this.data.details.description;

		this.buttons = ['0|Cancel', '1|*Save'];
		if (!this.isNew) this.buttons.push('2|<!Delete');
	}

	modalEvent(event) {
		if (!event.data || !event.data.id || event.data.id === 0) {
			this.modal.close();
			return;
		}

		switch (event.data.id) {
			case 1:
				// Save structure record
				this.api.sales.saveStructure(this.data.details, response => {
					this.modal.close(response.data);
					if (this.data.details.id === 'new') {
						this.app.notifications.showSuccess(this.typeDescription + ' created.');
					} else {
						this.app.notifications.showSuccess(this.typeDescription + ' updated.');
					}
				}, response => {
					this.app.notifications.showDanger(response.message);
				});
				break;

			case 2:
				if (this.data.children || this.data.products) {
					const stuff = [];
					if (this.data.children) stuff.push('areas');
					if (this.data.products) stuff.push('products');

					if (!confirm('Are you sure you want to delete all associated ' + stuff.join(' and ') + ' from this project?')) return;
				}

				// Delete structure record
				this.api.sales.deleteStructure(this.data.details.id, () => {
					this.app.notifications.showSuccess(this.typeDescription + ' deleted.');
					this.modal.close('deleted');
				}, response => {
					this.app.notifications.showDanger(response.message);
				});
				break;
		}
	}

}
