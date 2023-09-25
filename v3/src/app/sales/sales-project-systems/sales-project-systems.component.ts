import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-sales-project-systems',
	templateUrl: './sales-project-systems.component.html'
})
export class SalesProjectSystemsComponent implements OnInit, OnDestroy {

	modules: any[];
	systems: any[] = [];

	private sub: any;

	constructor(
		public app: AppService,
		private api: ApiService
	) { }

	ngOnInit() {
		this.sub = this.app.productOwnerChanged.subscribe(() => this.refresh());
		this.refresh();
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

	refresh() {
		this.api.sales.listProjectSystems(this.app.selectedProductOwner, response => {
			this.modules = response.data.modules || [];
			this.systems = response.data.systems || [];
			this.app.resolveProductOwners(response);

			// Prepare modules
			const moduleIndex = {};
			this.modules.forEach(item => {
				item.items = [];
				moduleIndex[item.id] = item;
			});

			this.systems.forEach(item => {
				// Add to module
				if (item.module_id) {
					const m = moduleIndex[item.module_id];
					if (m) m.items.push(item);
				}
			});

		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	moveModuleUp(m) {
		this.api.sales.moveProjectModuleUp(m.id, () => this.refresh(), response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	moveModuleDown(m) {
		this.api.sales.moveProjectModuleDown(m.id, () => this.refresh(), response => {
			this.app.notifications.showDanger(response.message);
		});
	}

}
