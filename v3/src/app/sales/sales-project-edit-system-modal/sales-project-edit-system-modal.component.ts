import { ModalService } from './../../shared/modal/modal.service';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { ModalComponent } from './../../shared/modal/modal.component';
import { Component, OnInit, ViewChild } from '@angular/core';
import { Pagination } from 'app/shared/pagination';

declare var Mangler: any;

@Component({
	selector: 'app-sales-project-edit-system-modal',
	templateUrl: './sales-project-edit-system-modal.component.html',
	styleUrls: ['./sales-project-edit-system-modal.component.less']
})
export class SalesProjectEditSystemModalComponent implements OnInit {

	@ViewChild(ModalComponent) modal: ModalComponent;

	tabs: any[] = [
		{ id: 'details', description: 'Details' },
		{ id: 'products', description: 'Add to toolbox' }
	];
	selectedTab = 'details';
	title = 'System details';
	buttons = ['0|Cancel'];

	listToolbox: any[] = [];
	listProducts: any[] = [];

	search = '';
	pagination = new Pagination();

	add: any[] = [];
	remove: any[] = [];

	details;
	editable = false;

	constructor(
		private app: AppService,
		private api: ApiService,
		private modalService: ModalService
	) { }

	ngOnInit() {
		this.api.sales.getSystem(this.modalService.data.id, this.modalService.data.si, response => {
			this.details = response.data.details;
			this.editable = response.data.editable;

			this.listProducts = response.data.products || [];
			this.listToolbox = [];
			this.add = [];
			this.remove = [];

			this.listProducts.forEach(item => {
				item.in_toolbox = !!item.in_system;
				if (item.in_system) {
					this.listToolbox.push(item);
				}
			});

			// Sort toolbox items
			this.listToolbox.sort((a, b) => a.sort_order < b.sort_order ? -1 : (a.sort_order > b.sort_order ? 1 : 0));

			this.buttons.push('1|*Save');
			this.buttons = this.buttons.slice();
			this.title = this.details.description;
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	selectTab(id) {
		this.selectedTab = id;
	}

	addItem(item) {
		let i = this.listProducts.indexOf(item);

		if (i !== -1) {
			item.in_toolbox = true;
			this.listToolbox.push(item);
			this.listToolbox = this.listToolbox.slice();

			i = this.remove.indexOf(item.id);
			if (i !== -1) this.remove.splice(i, 1);
			if (!item.in_system) this.add.push(item.id);
		}
	}

	removeItem(item) {
		let i = this.listToolbox.indexOf(item);

		if (i !== -1) {
			item.in_toolbox = false;
			this.listToolbox.splice(i, 1);
			this.listToolbox = this.listToolbox.slice();

			i = this.add.indexOf(item.id);
			if (i !== -1) this.add.splice(i, 1);
			if (item.in_system) this.remove.push(item.id);
		}
	}

	modalHandler(event) {
		if (event.data) {
			switch (event.data.id) {
				case 1:
					const data = Mangler.clone(this.details);
					data.add = this.add;
					data.remove = this.remove;
					data.order = this.listToolbox.map(item => item.id);

					this.api.sales.saveSystem(data, () => {
						this.app.notifications.showSuccess('System updated.');
						this.modal.close();
					}, response => {
						this.app.notifications.showDanger(response.message);
					});
					break;

				default:
					this.modal.close();
					break;
			}
		} else {
			this.modal.close();
		}
	}

	toolboxDrop(event) {
		// Update data model
		const previousIndex = event.previousIndex;
		const currentIndex = event.currentIndex;

		if (previousIndex === currentIndex) return; // No change

		const item = this.listToolbox.splice(previousIndex, 1)[0];
		this.listToolbox.splice(currentIndex, 0, item);
	}

}
