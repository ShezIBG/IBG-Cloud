import { Router, ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit } from '@angular/core';

@Component({
	selector: 'app-relay-overview',
	templateUrl: './relay-overview.component.html',
	styleUrls: ['../relay.module.less']
})
export class RelayOverviewComponent implements OnInit {

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
		this.api.relay.getOverview(res => {
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
			building.categories = [];
		});

		this.data.device_categories.forEach(item => {
			const building = buildingIndex[item.building_id];
			if (building) {
				building.categories.push(item);
			}
		});

		// No need to generate layout for more than 6 buildings, as they will be displayed as a list
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
