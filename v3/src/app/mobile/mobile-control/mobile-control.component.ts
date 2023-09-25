import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { ApiService } from 'app/api.service';
import { AppService } from 'app/app.service';
import { KnxValue } from 'app/control/knx-value';
import { ChartjsComponent } from 'app/shared/chartjs/chartjs.component';
import { MobileService } from '../mobile.service';

@Component({
	selector: 'app-mobile-control',
	templateUrl: './mobile-control.component.html',
	styleUrls: ['./mobile-control.component.less']
})
export class MobileControlComponent implements OnInit {

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
		private route: ActivatedRoute,
		private router: Router,
		public mobile: MobileService,
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			this.mobile.buildingId = params['buildingId'];

			this.api.control.getBuilding(this.mobile.buildingId, res => {
				this.data = res.data;

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
			}, res => {
				this.router.navigate(['..'], { replaceUrl: true });
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
