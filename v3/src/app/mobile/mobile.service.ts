import { Injectable } from '@angular/core';
import { Router } from '@angular/router';
import { ApiService } from 'app/api.service';
import { AppService } from 'app/app.service';
import { ModuleService } from 'app/shared/module.service';

@Injectable()
export class MobileService extends ModuleService {

	buildingInfo = null;

	private _buildingId;
	get buildingId() { return this._buildingId; }
	set buildingId(value) {
		if (value !== this._buildingId) {
			this._buildingId = value;
			this.buildingInfo = null;
			if (this._buildingId) {
				this.api.mobile.getBuilding(this._buildingId, response => {
					// Protect against quick repeats
					if (this._buildingId === value) this.buildingInfo = response.data;
				});
			}
		}
	}

	moduleName = '';

	constructor(
		public app: AppService,
		private api: ApiService,
		private router: Router
	) {
		super(app);
	}

	openBuildingSelect(moduleName = '') {
		this.moduleName = moduleName;
		this.router.navigate(['/mobile/select-building']);
	}

	selectBuilding(buildingId) {
		this.buildingId = buildingId;

		const route = ['/mobile', this.buildingId];
		if (this.moduleName) {
			this.moduleName.split('/').forEach(chunk => {
				if (chunk) route.push(chunk);
			});
		}

		this.router.navigate(route, { replaceUrl: true });
	}

}
