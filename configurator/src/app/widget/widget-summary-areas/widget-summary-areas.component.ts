import { EntityManager } from './../../entity/entity-manager';
import { AppService } from './../../app.service';
import { Component } from '@angular/core';

@Component({
	selector: 'widget-summary-areas',
	templateUrl: './widget-summary-areas.component.html'
})
export class WidgetSummaryAreasComponent {

	em: EntityManager;

	constructor(public app: AppService) {
		this.em = app.entityManager;
	}

}
