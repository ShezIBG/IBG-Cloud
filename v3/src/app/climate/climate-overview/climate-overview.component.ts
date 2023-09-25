import { Router, ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit } from '@angular/core';

@Component({
	selector: 'app-climate-overview',
	templateUrl: './climate-overview.component.html',
	styleUrls: ['./climate-overview.component.less']
})
export class ClimateOverviewComponent implements OnInit {

	data: any = null;
	buildingLayout: any[] = [];
	search = '';

	constructor(
		private app: AppService,
		private api: ApiService,
		private router: Router,
		private route: ActivatedRoute
	) { }

	ngOnInit() {
		this.api.climate.getOverview(res => {
			this.data = res.data;

			this.app.header.clearCrumbs();

			if (this.data.buildings.length === 1) {
				this.router.navigate(['../building', this.data.buildings[0].id], { relativeTo: this.route });
			}

			this.processData();
		});
	}

	processData() {
		const buildingIndex = {};
		this.data.buildings.forEach(building => {
			buildingIndex[building.id] = building;
			building.type_w = 0;
			building.type_d = 0;
			building.type_o = 0;
			building.type_c = 0;
		});

		this.data.device_types.forEach(item => {
			const building = buildingIndex[item.building_id];
			if (building) {
				building.type_w = item.type_w || 0;
				building.type_d = item.type_d || 0;
				building.type_o = item.type_o || 0;
				building.type_c = item.type_c || 0;
			}
		});

		// No need to genrate layout for more than 6 buildings, as they will be displayed as a list
		if (this.data.buildings.length > 6 || this.data.buildings.length < 2) return;

		this.buildingLayout = [];
		const b = this.data.buildings;

		switch (b.length) {
			case 2:
			case 3:
				// Display in rows
				for (let i = 0; i < b.length; i++) {
					this.buildingLayout.push([b[i], null]);
				}
				break;

			case 4:
			case 6:
				// Even numbers, display in grid
				for (let i = 0; i < b.length; i += 2) {
					this.buildingLayout.push([b[i], b[i + 1]]);
				}
				break;

			case 5:
				// Special layout for 5
				this.buildingLayout.push([b[0], null]);
				this.buildingLayout.push([b[1], b[2]]);
				this.buildingLayout.push([b[3], b[4]]);
				break;
		}
	}

	getAddress(b: any) {
		const result = [];
		if (b.address) {
			('' + b.address).split(',').forEach(s => result.push(s.trim()));
		}
		if (b.posttown) result.push(b.posttown);
		if (b.postcode) result.push(b.postcode.toUpperCase());

		return result.join(', ');
	}

}
