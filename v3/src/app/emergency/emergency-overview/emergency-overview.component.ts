import { AppService } from './../../app.service';
import { ApiService } from './../../api.service';
import { Router, ActivatedRoute } from '@angular/router';
import { Component, OnInit } from '@angular/core';

@Component({
	selector: 'app-emergency-overview',
	templateUrl: './emergency-overview.component.html',
	styleUrls: ['./emergency-overview.component.less']
})
export class EmergencyOverviewComponent implements OnInit {

	data: any = null;

	buildingLayout: any[] = [];

	buildingsPass = 0;
	buildingsPassPerc = 0;
	buildingsWarning = 0;
	buildingsWarningPerc = 0;
	buildingsFail = 0;
	buildingsFailPerc = 0;
	buildingsCount = 0;

	search = '';

	constructor(
		private app: AppService,
		private api: ApiService,
		private router: Router,
		private route: ActivatedRoute
	) { }

	ngOnInit() {
		this.api.emergency.getOverview(res => {
			this.data = res.data;

			this.app.header.clearCrumbs();

			if (this.data.buildings.length === 1) {
				this.router.navigate(['../building', this.data.buildings[0].id], { relativeTo: this.route });
			}

			this.processData();
		});
	}

	processData() {
		// Calculate overall status
		const statusCount = { '-1': 0, '0': 0, '1': 0 };
		this.data.buildings.forEach(building => {
			statusCount[building.status] += 1;
		});

		if (statusCount['-1']) {
			this.data.status = 'fail';
			this.data.statusCount = statusCount['-1'] + statusCount['0'];
		} else if (statusCount['0']) {
			this.data.status = 'warning';
			this.data.statusCount = statusCount['0'];
		} else {
			this.data.status = 'pass';
			this.data.statusCount = statusCount['1'];
		}

		this.buildingsPass = statusCount['1'];
		this.buildingsWarning = statusCount['0'];
		this.buildingsFail = statusCount['-1'];
		this.buildingsCount = this.data.buildings.length || 0;

		if (this.buildingsCount) {
			this.buildingsPassPerc = Math.floor(this.buildingsPass / this.buildingsCount * 100);
			this.buildingsWarningPerc = Math.floor(this.buildingsWarning / this.buildingsCount * 100);
			this.buildingsFailPerc = Math.floor(this.buildingsFail / this.buildingsCount * 100);
		}

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
