import { ChartjsComponent } from './../../shared/chartjs/chartjs.component';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-climate-building',
	templateUrl: './climate-building.component.html',
	styleUrls: ['./climate-building.component.less']
})
export class ClimateBuildingComponent implements OnInit, OnDestroy {

	id: number;
	data: any = null;

	typeNames = ['Wall-mounted', 'Ducted', 'Outdoor', 'Ceiling'];
	typeValues = [];
	typeOptions = {
		elements: {
			center: {
				textTop: 0,
				text: 'Aircon Units',
				color: '#333',
				fontStyle: 'Quicksand',
				maxFontSize: 15,
				sidePadding: 15
			}
		}
	};

	ChartjsComponent = ChartjsComponent;

	private sub: any;

	constructor(
		private app: AppService,
		private api: ApiService,
		private route: ActivatedRoute
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			this.id = params['id'];

			this.api.climate.getBuilding(this.id, res => {
				this.data = res.data;

				this.app.header.clearCrumbs();
				if (this.data && this.data.building_count > 1) this.app.header.addCrumb({ description: 'Buildings', route: '/climate' });
				this.app.header.addCrumb({ description: this.data.building.description, compact: true });

				this.typeOptions.elements.center.textTop = 0;

				this.typeValues = [
					this.data.device_types.type_w,
					this.data.device_types.type_d,
					this.data.device_types.type_o,
					this.data.device_types.type_c
				];
				this.typeOptions.elements.center.textTop += this.data.device_types.type_w + this.data.device_types.type_d + this.data.device_types.type_o + this.data.device_types.type_c;
				this.typeOptions.elements.center.text = this.typeOptions.elements.center.textTop === 1 ? 'Aircon Unit' : 'Aircon Units';
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

	isScheduleOutOfRange(s) {
		if (s.device_min_setpoint && s.schedule_min_setpoint && s.schedule_min_setpoint < s.device_min_setpoint) return true;
		if (s.device_max_setpoint && s.schedule_max_setpoint && s.schedule_max_setpoint > s.device_max_setpoint) return true;
		return false;
	}

}
