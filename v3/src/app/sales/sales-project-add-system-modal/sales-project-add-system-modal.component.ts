import { AppService } from './../../app.service';
import { ModalService } from './../../shared/modal/modal.service';
import { ApiService } from './../../api.service';
import { ModalComponent } from './../../shared/modal/modal.component';
import { Component, OnInit, ViewChild } from '@angular/core';

declare var Mangler: any;

@Component({
	selector: 'app-sales-project-add-system-modal',
	templateUrl: './sales-project-add-system-modal.component.html'
})
export class SalesProjectAddSystemModalComponent implements OnInit {

	@ViewChild(ModalComponent) modal: ModalComponent;

	tabs: any[] = [
		{ id: 'select', description: 'Add / Remove' },
		{ id: 'new', description: 'Create new system' }
	];
	selectedTab = 'select';
	buttons: any[] = [];

	projectId = '';

	modules = [];
	moduleIndex = {};

	add: any[] = [];
	remove: any[] = [];

	details: any = {
		id: 'new',
		description: '',
		module_id: null
	};

	deletedProductsCount = 0;

	constructor(
		private app: AppService,
		private api: ApiService,
		private modalService: ModalService
	) { }

	ngOnInit() {
		this.projectId = this.modalService.data;

		this.refreshButtons();
		this.api.sales.getProjectSystems(this.projectId, response => {
			this.add = [];
			this.remove = [];
			this.modules = response.data.modules;

			this.moduleIndex = Mangler.index(this.modules, 'id');

			this.modules.forEach(m => {
				m.systemsInProject = [];
				m.systemsAvailable = [];
			});

			response.data.systems.forEach(s => {
				s.subtitle = '';
				if (s.product_count) {
					s.subtitle = s.product_count === 1 ? '(1 product)' : '(' + s.product_count + ' products)';
				}

				const m = this.moduleIndex[s.module_id];
				if (m) {
					if (s.in_project) {
						m.systemsInProject.push(s);
					} else {
						m.systemsAvailable.push(s);
					}
				}
			});

			// Auto-select first module for new systems
			if (this.modules.length) this.details.module_id = this.modules[0].id;

			// Automatically set owner of new system
			this.details.owner_level = 'SI';
			this.details.owner_id = response.data.system_integrator_id;
		});
	}

	refreshButtons() {
		if (this.selectedTab === 'select') {
			this.buttons = [];
			if (this.add.length > 0 || this.remove.length > 0) this.buttons.push('1|*Update systems');
			this.buttons.push('0|Cancel');
		} else {
			this.buttons = ['2|*Create new system'];
		}

		this.buttons = this.buttons.slice();
	}

	selectTab(id) {
		this.selectedTab = id;
		this.refreshButtons();
	}

	refreshDeletedProductsCount() {
		this.deletedProductsCount = 0;
		this.modules.forEach(m => {
			m.systemsAvailable.forEach(s => {
				if (s.product_count) this.deletedProductsCount += s.product_count;
			});
		});
	}

	addSystem(s) {
		const m = this.moduleIndex[s.module_id];
		if (!m) return;

		let i = m.systemsAvailable.indexOf(s);

		if (i !== -1) {
			m.systemsAvailable.splice(i, 1);
			m.systemsAvailable = m.systemsAvailable.slice();

			m.systemsInProject.push(s);
			m.systemsInProject = m.systemsInProject.slice();

			i = this.remove.indexOf(s.id);
			if (i !== -1) this.remove.splice(i, 1);
			if (!s.in_project) this.add.push(s.id);

			this.refreshButtons();
		}

		this.refreshDeletedProductsCount();
	}

	removeSystem(s) {
		const m = this.moduleIndex[s.module_id];
		if (!m) return;

		let i = m.systemsInProject.indexOf(s);

		if (i !== -1) {
			m.systemsInProject.splice(i, 1);
			m.systemsInProject = m.systemsInProject.slice();

			m.systemsAvailable.push(s);
			m.systemsAvailable = m.systemsAvailable.slice();

			i = this.add.indexOf(s.id);
			if (i !== -1) this.add.splice(i, 1);
			if (s.in_project) this.remove.push(s.id);

			this.refreshButtons();
		}

		this.refreshDeletedProductsCount();
	}

	modalHandler(event) {
		if (event.data) {
			switch (event.data.id) {
				case 1:
					if (this.deletedProductsCount) {
						if (!confirm('Are you sure you want to remove ' + this.deletedProductsCount + ' ' + (this.deletedProductsCount === 1 ? 'product' : 'products') + ' from this project?')) return;
					}

					this.api.sales.updateProjectSystems(this.projectId, this.add, this.remove, response => {
						this.app.notifications.showSuccess('Systems updated.');
						this.modal.close(response.data);
					}, response => {
						this.app.notifications.showDanger(response.message);
					});
					break;

				case 2:
					this.api.sales.saveSystem(this.details, response => {
						this.app.notifications.showSuccess('System created.');
						this.api.sales.updateProjectSystems(this.projectId, [response.data], [], () => {
							this.modal.close(['new', response.data]);
						}, () => {
							this.modal.close();
						});
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

}
