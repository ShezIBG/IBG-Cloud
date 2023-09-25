import { AppService } from './../../app.service';
import { EntityManager } from './../../entity/entity-manager';
import { Component } from '@angular/core';

@Component({
	selector: 'widget-summary-routers',
	templateUrl: './widget-summary-routers.component.html'
})
export class WidgetSummaryRoutersComponent {

	em: EntityManager;

	constructor(public app: AppService) {
		this.em = app.entityManager;
	}

	getDevices() {
		const e = this.em.entities;
		return e.pm12.length + e.abb_meter.length + e.mbus_master.length + e.mbus_device.length + e.olt.length + e.onu.length + e.dali_light.length + e.rs485.length;
	}

}
