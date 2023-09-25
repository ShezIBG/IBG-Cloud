import { UIElementModalComponent } from './../../shared/ui-element-modal/ui-element-modal.component';
import { SalesProjectCloneModalComponent } from './../sales-project-clone-modal/sales-project-clone-modal.component';
import { SalesProjectLineEditModalComponent } from './../sales-project-line-edit-modal/sales-project-line-edit-modal.component';
import { SalesProjectEditSystemModalComponent } from './../sales-project-edit-system-modal/sales-project-edit-system-modal.component';
import { SalesProjectProductAddModalComponent } from './../sales-project-product-add-modal/sales-project-product-add-modal.component';
import { SalesProjectEditStructureModalComponent } from './../sales-project-edit-structure-modal/sales-project-edit-structure-modal.component';
import { SalesService } from './../sales.service';
import { SalesProjectAddSystemModalComponent } from './../sales-project-add-system-modal/sales-project-add-system-modal.component';
import { ActivatedRoute, Router } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy, NgModuleRef } from '@angular/core';

declare var Mangler: any;

@Component({
	selector: 'app-sales-project-editor',
	templateUrl: './sales-project-editor.component.html',
	styleUrls: ['./sales-project-editor.component.less']
})
export class SalesProjectEditorComponent implements OnInit, OnDestroy {

	projectId: any;
	data: any;
	editable = false;

	toolboxHover: any = null;
	toolboxSearch = '';

	unsynced: any = {
		price: [],
		labour: [],
		subscription: []
	};

	_collapsed = true;
	collapsedExceptions = [];

	get collapsed() { return this._collapsed; }
	set collapsed(value) {
		this._collapsed = value;
		this.collapsedExceptions = [];
		this.refresh();
	}

	get expand() { return !this.collapsed; }
	set expand(value) { this.collapsed = !value; }

	// Makes sure spamming the +/- buttons will not do a full refresh every time
	// Price totals will be refreshed after a delay once the user stops spamming
	increaseRefreshTimer = null;

	_showAllSystems = false;

	get showAllSystems() { return this._showAllSystems; }
	set showAllSystems(value) {
		this._showAllSystems = value;
		this.refresh();
	}

	private sub: any;

	constructor(
		private app: AppService,
		private api: ApiService,
		public sales: SalesService,
		private route: ActivatedRoute,
		private router: Router,
		private moduleRef: NgModuleRef<any>
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			setTimeout(() => {
				this.app.header.setTab('editor');
				this.app.header.addButton({
					icon: 'md md-content-copy',
					text: 'Clone this project',
					callback: () => this.cloneProject()
				});
			}, 0);
			this.projectId = params['projectId'];
			this.refresh(() => this.getUnsynced());
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

	private selectStructureById(id) { this.sales.selectedStructure = Mangler.findOne(this.data.structure, { id }) || null; }
	private selectModuleById(id) { this.sales.selectedModule = Mangler.findOne(this.data.modules, { id }) || null; }
	private selectSystemById(id) { this.sales.selectedSystem = Mangler.findOne(this.data.systems, { id }) || null; }
	private selectLineById(id) { this.sales.selectedProjectLine = Mangler.findOne(this.data.lines, { id }) || null; }

	private getUnsynced() {
		if (!this.editable) return;

		this.api.sales.getUnsyncedProjectLines(this.projectId, response => {
			this.unsynced = response.data;
		});
	}

	refresh(callback: Function = null) {
		this.api.sales.getProjectLines({

			id: this.projectId,
			structure_list: Mangler.extract(this.sales.selectedStructure, 'id'),
			module_list: this.sales.selectedModule ? [this.sales.selectedModule.id] : [],
			system_list: Mangler.extract(this.sales.selectedSystem, 'id'),
			show_all_systems: this.showAllSystems ? 1 : 0

		}, response => {
			this.data = response.data;

			// Remove collapsed items from project lines
			const collapsedParents = {};
			this.data.lines = this.data.lines.filter(item => {
				if (item.parent_id === null) {
					// Top level item
					item.collapsed = this.isCollapsed(item.id);
					if (item.collapsed) collapsedParents[item.id] = item;
					return true;
				} else {
					// Accessory
					item.collapsed = false;
					const parent = collapsedParents[item.parent_id];
					if (parent) {
						parent.subscription_count += item.subscription_count;
					}
					return !parent;
				}
			});

			// Set editable flag
			this.editable = this.data.project.stage === 'lead' || this.data.project.stage === 'survey';

			// Add systems to modules
			const moduleIndex = Mangler.index(this.data.modules, 'id');
			this.data.modules.forEach(m => m.systems = []);
			this.data.systems.forEach(s => {
				const m = moduleIndex[s.module_id];
				if (m) m.systems.push(s);
			});

			// Check if selected items are still in the data
			if (this.sales.selectedStructure) this.selectStructureById(this.sales.selectedStructure.id);
			if (this.sales.selectedModule) this.selectModuleById(this.sales.selectedModule.id);
			if (this.sales.selectedSystem) this.selectSystemById(this.sales.selectedSystem.id);
			if (this.sales.selectedProjectLine) this.selectLineById(this.sales.selectedProjectLine.id);

			// Calculate floor totals
			let floor = null;
			this.data.structure.forEach(item => {
				item.hidden = false;

				switch (item.type) {
					case 'floor':
						item.total = 0;
						floor = item;
						break;

					case 'area':
						if (floor) item.hidden = !this.sales.isFloorExpanded(floor.id);
						if (floor && item.total) {
							floor.total += item.total;
						}
						break;
				}
			});

			// Calculate module totals
			this.data.modules.forEach(m => {
				m.total = null;
				m.systems.forEach(s => {
					if (s.total) {
						m.total = m.total ? m.total + s.total : s.total;
					}
				});
			});

			if (callback) callback();

		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	addRemoveSystem() {
		const modalSub = this.app.modal.modalService.modalClosed.subscribe(event => {
			modalSub.unsubscribe();
			if (event.data && Mangler.isArray(event.data)) {
				if (event.data[0] === 'new') {
					// Auto-select the new system
					this.selectSystem({ id: event.data[1] });
				} else {
					if (this.sales.selectedSystem) {
						const i = event.data.indexOf(this.sales.selectedSystem.id);
						if (i === -1) {
							// System is no longer in the list, select all
							this.selectSystem(null);
						} else {
							// System is still in the list, refresh
							this.refresh();
						}
					} else {
						// No system is selected, just refresh as is
						this.refresh();
					}
				}
			}
		});

		this.app.modal.open(SalesProjectAddSystemModalComponent, this.moduleRef, this.projectId);
	}

	selectStructure(item) {
		if (item && !Mangler.isObject(item)) {
			// A structure ID was passed, resolve
			item = Mangler.findOne(this.data.structure, { id: item }) || null;
		}

		if (item && item.type === 'floor') {
			// Toggle expansion
			if (this.sales.isFloorExpanded(item.id)) {
				if (this.sales.selectedStructure === item) this.sales.setFloorExpanded(item.id, false);
			} else {
				this.sales.setFloorExpanded(item.id, true);
			}
		}

		if (item && item.type === 'area') {
			// Make sure floor is expanded when selecting a child area
			const floor = Mangler.findOne(this.data.structure, { id: item.floor_id }) || null;
			if (floor) {
				if (!this.sales.isFloorExpanded(floor.id)) {
					this.sales.setFloorExpanded(floor.id, true);
				}
			}
		}

		this.sales.selectedStructure = item;
		this.refresh();
	}

	selectModule(item) {
		if (item) {
			// Toggle expansion
			if (this.sales.isModuleExpanded(item.id)) {
				if (this.sales.selectedModule === item) this.sales.setModuleExpanded(item.id, false);
			} else {
				this.sales.setModuleExpanded(item.id, true);
			}
		}

		this.sales.selectedModule = item;
		this.refresh();
	}

	selectSystem(item) {
		if (item && !Mangler.isObject(item)) {
			// A system ID was passed, resolve object
			item = Mangler.findOne(this.data.systems, { id: item }) || null;
		}

		if (item) {
			// Make sure parent module is expanded when selecting a system
			const m = Mangler.findOne(this.data.modules, { id: item.module_id }) || null;
			if (m) this.sales.setModuleExpanded(m.id, true);
		}

		this.sales.selectedSystem = item;
		this.refresh();
	}

	selectProjectLine(item) {
		this.sales.selectedProjectLine = item;
	}

	addProductFromToolbox(item) {
		if (!this.sales.selectedStructure) return;

		if (item.is_single) {
			const modalSub = this.app.modal.modalService.modalClosed.subscribe(event => {
				modalSub.unsubscribe();
				this.refresh(() => {
					if (event && event.data) this.selectLineById(event.data);
				});
			});

			this.app.modal.open(SalesProjectLineEditModalComponent, this.moduleRef, {
				id: 'new',
				project_id: this.projectId,
				product_id: item.id,
				structure_id: this.sales.selectedStructure.id,
				system_id: item.system_id,
				is_single: 1
			});
		} else {
			this.api.sales.addProjectLine({
				id: this.projectId,
				product_id: item.id,
				structure_id: this.sales.selectedStructure.id,
				system_id: item.system_id
			}, response => {
				this.refresh(() => {
					this.selectLineById(response.data);
				});
			}, response => {
				this.app.notifications.showDanger(response.message);
			});
		}
	}

	addProduct() {
		if (!this.data.systems.length || !this.sales.selectedStructure) return;

		const modalSub = this.app.modal.modalService.modalClosed.subscribe(event => {
			modalSub.unsubscribe();
			if (event.data) {
				this.api.sales.addProjectLine({
					id: this.projectId,
					product_id: event.data.product.id,
					structure_id: this.sales.selectedStructure.id,
					system_id: event.data.system,
					toolbox: event.data.toolbox
				}, response => {
					this.refresh(() => {
						this.selectLineById(response.data);
					});
				}, response => {
					this.app.notifications.showDanger(response.message);
				});
			}
		});

		let selectedSystem = null;
		if (this.sales.selectedModule && this.sales.selectedModule.systems[0]) selectedSystem = this.sales.selectedModule.systems[0].id || null;
		if (this.sales.selectedSystem) selectedSystem = this.sales.selectedSystem.id;
		if (this.sales.selectedProjectLine) selectedSystem = this.sales.selectedProjectLine.system_id;

		this.app.modal.open(SalesProjectProductAddModalComponent, this.moduleRef, {
			moduleList: Mangler.clone(this.data.modules),
			systemList: Mangler.clone(this.data.systems),
			system: selectedSystem,
			owner: 'SI-' + this.data.project.system_integrator_id
		});
	}

	addCustomLine() {
		const modalSub = this.app.modal.modalService.modalClosed.subscribe(event => {
			modalSub.unsubscribe();
			this.refresh(() => {
				if (event && event.data) this.selectLineById(event.data);
			});
		});

		let selectedSystem = null;
		if (this.data.modules && this.data.modules[0] && this.data.modules[0].systems[0]) selectedSystem = this.data.modules[0].systems[0].id;
		if (this.sales.selectedModule && this.sales.selectedModule.systems[0]) selectedSystem = this.sales.selectedModule.systems[0].id || null;
		if (this.sales.selectedSystem) selectedSystem = this.sales.selectedSystem.id;

		this.app.modal.open(SalesProjectLineEditModalComponent, this.moduleRef, {
			id: 'new',
			project_id: this.projectId,
			product_id: null,
			structure_id: this.sales.selectedStructure.id,
			system_id: selectedSystem,
			description: ''
		});
	}

	increaseLineQuantity(item, quantity) {
		this.api.sales.increaseProjectLine({
			id: item.id,
			quantity
		}, response => {
			item.quantity = response.data;
			clearTimeout(this.increaseRefreshTimer);
			this.increaseRefreshTimer = setTimeout(() => {
				this.refresh();
			}, 1000);
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	copyLine(item) {
		this.api.sales.copyProjectLine(item.id, response => {
			this.refresh(() => {
				this.selectLineById(response.data);
			});
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	editLine(item) {
		const modalSub = this.app.modal.modalService.modalClosed.subscribe(event => {
			modalSub.unsubscribe();
			this.refresh(() => {
				if (event && event.data) this.selectLineById(event.data);
			});
		});

		this.app.modal.open(SalesProjectLineEditModalComponent, this.moduleRef, { id: item.id });
	}

	deleteLine(item) {
		if (confirm('Are you sure you want to delete "' + (item.product_id !== null ? item.model : item.description) + '"?')) {
			this.api.sales.deleteProjectLine(item.id, () => {
				if (this.sales.selectedProjectLine) {
					if (item.id === this.sales.selectedProjectLine.id) this.sales.selectedProjectLine = null;
				}
				this.refresh();
			}, response => {
				this.app.notifications.showDanger(response.message);
			});
		}
	}

	editStructure(type = null) {
		const success = response => {
			const modalSub = this.app.modal.modalService.modalClosed.subscribe(event => {
				modalSub.unsubscribe();
				if (event.data) {
					this.selectStructure(event.data === 'deleted' ? null : { id: event.data, type: type });
				}
			});

			this.app.modal.open(SalesProjectEditStructureModalComponent, this.moduleRef, response.data);
		};

		const fail = response => {
			this.app.notifications.showDanger(response.message);
		};

		if (!type) {
			// Edit selected structure record
			if (!this.sales.selectedStructure) return;
			this.api.sales.getStructure(this.sales.selectedStructure.id, success, fail);

		} else {
			// Add specified structure type
			const data = {
				type,
				project_id: this.projectId,
				parent_id: type === 'area' ? this.sales.selectedStructure.floor_id : null
			};
			this.api.sales.newStructure(data, success, fail);
		}
	}

	editSystem() {
		if (!this.sales.selectedSystem) return;

		const modalSub = this.app.modal.modalService.modalClosed.subscribe(event => {
			modalSub.unsubscribe();
			this.refresh();
		});

		this.app.modal.open(SalesProjectEditSystemModalComponent, this.moduleRef, { id: this.sales.selectedSystem.id, si: this.data.project.system_integrator_id });
	}

	dismissSyncWarning() {
		this.unsynced = {
			price: [],
			labour: [],
			subscription: []
		};
	}

	syncPrices() {
		if (!this.editable) return;

		this.api.sales.syncProjectLines(this.projectId, () => {
			this.getUnsynced();
			this.refresh();
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	hasPriceWarning(id) {
		return this.unsynced.price.indexOf(id) !== -1;
	}

	hasLabourWarning(id) {
		return this.unsynced.labour.indexOf(id) !== -1;
	}

	isCollapsed(itemId) {
		const state = this.collapsed;
		return this.collapsedExceptions.indexOf(itemId) === -1 ? state : !state;
	}

	toggleCollapsed(itemId) {
		if (itemId) {
			const i = this.collapsedExceptions.indexOf(itemId);
			if (i === -1) {
				this.collapsedExceptions.push(itemId);
			} else {
				this.collapsedExceptions.splice(i, 1);
			}
		}

		this.refresh();
	}

	cloneProject() {
		const modalSub = this.app.modal.modalService.modalClosed.subscribe(event => {
			modalSub.unsubscribe();

			if (event.data) {
				this.api.sales.cloneProject(event.data, cloneResponse => {
					this.app.notifications.showSuccess('Project cloned.');
					this.router.navigate(['../..', cloneResponse.data, 'details'], { replaceUrl: true, relativeTo: this.route });
				}, cloneResponse => {
					this.app.notifications.showDanger(cloneResponse.message);
				});
			}
		});

		this.app.modal.open(SalesProjectCloneModalComponent, this.moduleRef, {
			id: this.projectId,
			description: this.data.project.description || '',
			clone: {
				systems: true,
				structure: true,
				products: true,
				proposal: true
			}
		});
	}

	isDiscontinued(id) {
		return id && this.data && this.data.discontinued.indexOf(id) !== -1;
	}

	editUI() {
		const modalSub = this.app.modal.modalService.modalClosed.subscribe(() => {
			this.refresh();
			modalSub.unsubscribe();
		});

		this.app.modal.open(UIElementModalComponent, null, 'project_editor');
	}

}
