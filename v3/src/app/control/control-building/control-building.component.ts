import { ChartjsComponent } from './../../shared/chartjs/chartjs.component';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';
import { KnxValue } from '../knx-value';

@Component({
	selector: 'app-control-building',
	templateUrl: './control-building.component.html'
})
export class ControlBuildingComponent implements OnInit, OnDestroy {

	id: number;
	data: any = null;

	typeNames = [];
	typeValues = [];
	typeOptions = {
		elements: {
			center: {
				textTop: 0,
				text: 'Lights',
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

			this.api.control.getBuilding(this.id, res => {
				this.data = res.data;

				this.app.header.clearCrumbs();
				if (this.data && this.data.building_count > 1) this.app.header.addCrumb({ description: 'Buildings', route: '/control' });
				this.app.header.addCrumb({ description: this.data.building.description, compact: true });

				this.typeOptions.elements.center.textTop = 0;

				this.typeValues = [];
				this.typeNames = [];
				let total = 0;
				this.data.device_types.forEach(item => {
					this.typeValues.push(item.item_count);
					this.typeNames.push(item.description);
					total += item.item_count;
				});

				this.data.device_statuses.forEach(item => {
					item.on_knx = new KnxValue(0, item.knx_datatype, item.knx_subtype, 1, true);
					item.off_knx = new KnxValue(0, item.knx_datatype, item.knx_subtype, 0, true);
				});

				this.typeOptions.elements.center.textTop += total;
				this.typeOptions.elements.center.text = this.typeOptions.elements.center.textTop === 1 ? 'Device' : 'Devices';
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

}
