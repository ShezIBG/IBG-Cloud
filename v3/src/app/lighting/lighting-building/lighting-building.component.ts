import { ChartjsComponent } from './../../shared/chartjs/chartjs.component';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Component, OnInit, OnDestroy } from '@angular/core';

@Component({
	selector: 'app-lighting-building',
	templateUrl: './lighting-building.component.html'
})
export class LightingBuildingComponent implements OnInit, OnDestroy {

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

			this.api.lighting.getBuilding(this.id, res => {
				this.data = res.data;

				this.app.header.clearCrumbs();
				if (this.data && this.data.building_count > 1) this.app.header.addCrumb({ description: 'Buildings', route: '/lighting' });
				this.app.header.addCrumb({ description: this.data.building.description, compact: true });

				this.typeOptions.elements.center.textTop = 0;

				this.typeValues = [];
				this.typeNames = [];
				let total = 0;
				this.data.device_types.forEach(item => {
					this.typeValues.push(item.light_count);
					this.typeNames.push(item.category);
					total += item.light_count;
				});

				this.typeOptions.elements.center.textTop += total;
				this.typeOptions.elements.center.text = this.typeOptions.elements.center.textTop === 1 ? 'Light' : 'Lights';
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
