import { AppService } from './../../app.service';
import { MySQLDateToISOPipe } from './../../shared/mysql-date-to-iso.pipe';
import { ApiService } from './../../api.service';
import { Component, OnInit, OnDestroy } from '@angular/core';
import { ActivatedRoute } from '@angular/router';

@Component({
	selector: 'app-emergency-building',
	templateUrl: './emergency-building.component.html',
	styleUrls: ['./emergency-building.component.less']
})
export class EmergencyBuildingComponent implements OnInit, OnDestroy {

	id: number;
	data: any = null;

	typeNames = [];
	typeValues = [];
	typeOptions = {
		colors: ['#5abf82', '#40a568', '#328051', '#245c3a', '#153723'],
		elements: {
			center: {
				textTop: 0,
				text: 'Light fittings',
				color: '#333',
				fontStyle: 'Quicksand',
				maxFontSize: 15,
				sidePadding: 15
			}
		}
	};

	lightsPass = 0;
	lightsPassPerc = 0;
	lightsWarning = 0;
	lightsWarningPerc = 0;
	lightsFail = 0;
	lightsFailPerc = 0;
	lightsCount = 0;

	scheduleTab = 'function';
	sortedFunctionList = [];
	sortedDurationList = [];

	// Dummy data
	reports = [
		{ datetime: '2019-10-14 05:15', pass: 7, fail: 0 },
		{ datetime: '2019-10-10 05:01', pass: 2, fail: 1 },
		{ datetime: '2019-10-06 05:23', pass: 6, fail: 1 }
	];

	private sub: any;

	constructor(
		private app: AppService,
		private api: ApiService,
		private route: ActivatedRoute
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			this.id = params['id'];

			this.api.emergency.getBuilding(this.id, res => {
				this.data = res.data;

				this.app.header.clearCrumbs();
				if (this.data && this.data.building_count > 1) this.app.header.addCrumb({ description: 'Buildings', route: '/emergency' });
				this.app.header.addCrumb({ description: this.data.building.description, compact: true });

				this.typeOptions.elements.center.textTop = 0;

				this.typeNames = [];
				this.typeValues = [];
				this.data.types.forEach(type => {
					this.typeNames.push(type.description);
					this.typeValues.push(type.light_count);
					this.typeOptions.elements.center.textTop += type.light_count;
				});

				this.typeOptions.elements.center.text = this.typeOptions.elements.center.textTop === 1 ? 'Light fitting' : 'Light fittings';

				this.sortedFunctionList = [];
				this.sortedDurationList = [];

				this.sortedFunctionList = this.data.groups.slice().sort((a, b) => {
					const dateA = a.function_test_datetime === null ? null : new Date(MySQLDateToISOPipe.transform(a.function_test_datetime));
					const dateB = b.function_test_datetime === null ? null : new Date(MySQLDateToISOPipe.transform(b.function_test_datetime));

					if (dateA === dateB) return a.description.localeCompare(b.description);
					if (dateA < dateB) return -1;
					if (dateA > dateB) return 1;
					return 0;
				});

				this.sortedDurationList = this.data.groups.slice().sort((a, b) => {
					const dateA = a.duration_test_datetime === null ? null : new Date(MySQLDateToISOPipe.transform(a.duration_test_datetime));
					const dateB = b.duration_test_datetime === null ? null : new Date(MySQLDateToISOPipe.transform(b.duration_test_datetime));

					if (dateA === dateB) return a.description.localeCompare(b.description);
					if (dateA < dateB) return -1;
					if (dateA > dateB) return 1;
					return 0;
				});

				this.lightsPass = this.data.building.light_pass;
				this.lightsWarning = this.data.building.light_warning;
				this.lightsFail = this.data.building.light_fail;
				this.lightsCount = this.data.building.light_count;

				if (this.lightsCount) {
					this.lightsPassPerc = Math.floor(this.lightsPass / this.lightsCount * 100);
					this.lightsWarningPerc = Math.floor(this.lightsWarning / this.lightsCount * 100);
					this.lightsFailPerc = Math.floor(this.lightsFail / this.lightsCount * 100);
				}
			});
		});
	}

	ngOnDestroy() {
		this.sub.unsubscribe();
	}

	getAddress(b: any) {
		const result = ['<b>' + b.description + '</b>'];
		if (b.address) {
			('' + b.address).split(',').forEach(s => result.push(s.trim()));
		}
		if (b.posttown) result.push(b.posttown);
		if (b.postcode) result.push(b.postcode);

		return result.join('<br>');
	}

	getChartColor(i) {
		return this.typeOptions.colors[i % this.typeOptions.colors.length];
	}

}
