import { SparklineChartComponent } from './../../sparkline-chart/sparkline-chart.component';
import { EntityManager } from './../../entity/entity-manager';
import { AppService } from './../../app.service';
import { Component } from '@angular/core';

@Component({
	selector: 'widget-device-types',
	templateUrl: './widget-device-types.component.html',
	styleUrls: ['./widget-device-types.component.css']
})
export class WidgetDeviceTypesComponent {

	em: EntityManager;
	types: any;

	names = [];
	values = [];

	constructor(public app: AppService) {
		this.em = app.entityManager;
		this.types = this.getDeviceTypes();
	}

	getDeviceTypes() {
		const types = ['abb_meter', 'pm12', 'mbus_master', 'mbus_device', 'olt', 'onu', 'dali_light'];
		const result = [];

		this.names = [];
		this.values = [];

		types.forEach(type => {
			if (this.em.entities[type].length) {
				result.push({
					description: this.em.getGroupName(type),
					count: this.em.entities[type].length
				});

				this.names.push(this.em.getGroupName(type));
				this.values.push(this.em.entities[type].length);
			}
		});

		return result;
	}

	getChartColor(i) {
		return SparklineChartComponent.colors[i];
	}

}
