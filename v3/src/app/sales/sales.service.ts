import { Util } from './../shared/util';
import { ModuleService } from './../shared/module.service';
import { Injectable } from '@angular/core';

declare var Mangler: any;

@Injectable()
export class SalesService extends ModuleService {

	projectId: any;
	projectDescription: any;
	projectPricing: any;
	projectFilters: any;
	showProjectFilters = false;

	private _selectedStructure: any = null;
	get selectedStructure() { return this._selectedStructure; }
	set selectedStructure(value) {
		const oldId = this._selectedStructure ? this._selectedStructure.id : null;
		this._selectedStructure = value;
		if (value && oldId !== value.id) Util.scrollIntoView('scrollto-structure-' + value.id);
	}

	private _selectedModule: any = null;
	get selectedModule() { return this._selectedModule; }
	set selectedModule(value) {
		const oldId = this._selectedModule ? this._selectedModule.id : null;
		this._selectedSystem = null;
		this._selectedModule = value;
		if (value && oldId !== value.id) Util.scrollIntoView('scrollto-module-' + value.id);
	}

	private _selectedSystem: any = null;
	get selectedSystem() { return this._selectedSystem; }
	set selectedSystem(value) {
		const oldId = this._selectedSystem ? this._selectedSystem.id : null;
		this._selectedModule = null;
		this._selectedSystem = value;
		if (value && oldId !== value.id) Util.scrollIntoView('scrollto-system-' + value.id);
	}

	private _selectedProjectLine: any = null;
	get selectedProjectLine() { return this._selectedProjectLine; }
	set selectedProjectLine(value) {
		const oldId = this._selectedProjectLine ? this._selectedProjectLine.id : null;
		this._selectedProjectLine = value;
		if (value && oldId !== value.id) Util.scrollIntoView('scrollto-product-' + value.id);
	}

	expandedModules = [];
	expandedFloors = [];

	setProjectHeader(id, description, projectNo, pricing) {
		this.projectId = id;
		this.projectDescription = '' + description + ' (#' + projectNo + ')';
		this.projectPricing = pricing;
		this.showProjectHeader();
	}

	showProjectHeader() {
		const projectBase = '/sales/project/' + this.projectId;
		const currentTab = this.app.header.activeTab;

		this.app.header.clearAll();
		this.app.header.addCrumbs([
			{ description: 'Projects', route: '/sales/project' },
			{ description: this.projectDescription, route: '/sales/project/' + this.projectId, compact: true }
		]);
		this.app.header.addTab({ id: 'summary', title: 'Summary', route: projectBase + '/summary' });
		this.app.header.addTab({ id: 'details', title: 'Details', route: projectBase + '/details' });
		this.app.header.addTab({ id: 'editor', title: 'Project Editor', route: projectBase + '/editor' });
		if (this.projectPricing) {
			this.app.header.addTab({ id: 'price-adjustments', title: 'Price Adjustments', route: projectBase + '/price-adjustments' });
			this.app.header.addTab({ id: 'cost-summary', title: 'Cost Summary', route: projectBase + '/cost-summary' });
			this.app.header.addTab({ id: 'po-request', title: 'PO Request', route: projectBase + '/po-request' });
			this.app.header.addTab({ id: 'itemised-quotation', title: 'Itemised Quotation', route: projectBase + '/itemised-quotation' });
			this.app.header.addTab({ id: 'proposal', title: 'Proposal', route: projectBase + '/proposal' });
		}

		if (currentTab) this.app.header.setTab(currentTab);
	}

	setModuleExpanded(id, state) {
		if (state) {
			if (!Mangler.first(this.expandedModules, id)) this.expandedModules.push(id);
		} else {
			Mangler.filter(this.expandedModules, { $ne: id });
		}
		this.expandedModules = this.expandedModules.slice();
	}

	isModuleExpanded(id) {
		return !!Mangler.first(this.expandedModules, id);
	}

	setFloorExpanded(id, state) {
		if (state) {
			if (!Mangler.first(this.expandedFloors, id)) this.expandedFloors.push(id);
		} else {
			Mangler.filter(this.expandedFloors, { $ne: id });
		}
		this.expandedFloors = this.expandedFloors.slice();
	}

	isFloorExpanded(id) {
		return !!Mangler.first(this.expandedFloors, id);
	}

}
