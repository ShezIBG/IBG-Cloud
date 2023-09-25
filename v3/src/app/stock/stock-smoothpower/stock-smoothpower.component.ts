import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

declare var Mangler: any;

@Component({
	selector: 'app-stock-smoothpower',
	templateUrl: './stock-smoothpower.component.html',
	styleUrls: ['./stock-smoothpower.component.less']
})
export class StockSmoothpowerComponent implements OnInit, OnDestroy {

	available = null;
	installed = null;
	search = '';

	count = {
		available: 0,
		installed: 0
	};

	canEdit = false;
	canInstall = false;

	private sub: any;
	private destroyed = false;
	private statusStack = null;

	constructor(
		public app: AppService,
		private api: ApiService
	) { }

	ngOnInit() {
		this.sub = this.app.productOwnerChanged.subscribe(() => this.refresh());
		this.refresh();
	}

	ngOnDestroy() {
		this.destroyed = true;
		this.sub.unsubscribe();
	}

	refresh() {
		this.api.stock.listSmoothPowerUnits(this.app.selectedProductOwner, response => {
			const list = response.data.list || [];
			this.app.resolveProductOwners(response);

			// Resolve access flags
			this.canEdit = response.data.can_edit;
			this.canInstall = response.data.can_install;

			// Separate list to available and installed
			this.available = Mangler.find(list, { building_id: null });
			this.installed = Mangler.find(list, { building_id: { $ne: null } });

			// Start resolving statuses
			this.statusStack = this.installed.slice();
			this.resolveStatus();

			this.app.header.clearAll();
			this.app.header.addCrumb({ description: 'SmoothPower Units' });
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	resolveStatus() {
		if (!this.statusStack || !this.statusStack.length || this.destroyed) return;

		const stack = this.statusStack;
		const unit = stack.shift();

		this.api.smoothpower.getUnitStatus(unit.id, response => {
			if (this.destroyed || stack !== this.statusStack) return;

			Mangler.merge(unit, {
				status: response.data.status,
				surge_status: response.data.surge_status,
				temp_top: response.data.temp_top,
				temp_bottom: response.data.temp_bottom,
				voltage_input: response.data.voltage_input,
				voltage_output: response.data.voltage_output,
				voltage_reduction: response.data.voltage_reduction
			});

			this.resolveStatus();
		}, () => {
			if (this.destroyed || stack !== this.statusStack) return;
			this.resolveStatus();
		});
	}

	removeUnit(item) {
		if (confirm('Are you sure you want to remove SmoothPower unit ' + item.serial + ' from the building it is installed?')) {
			this.api.smoothpower.uninstallUnit(item.id, () => {
				this.app.notifications.showSuccess('SmoothPower unit removed.');
				this.refresh();
			}, response => {
				this.app.notifications.showDanger(response.message);
				this.refresh();
			});
		}
	}

}
