import { AppService } from './../../app.service';
import { EntityManager } from './../../entity/entity-manager';
import { Component } from '@angular/core';

@Component({
	selector: 'widget-summary-points',
	templateUrl: './widget-summary-points.component.html'
})
export class WidgetSummaryPointsComponent {

	em: EntityManager;

	constructor(public app: AppService) {
		this.em = app.entityManager;
	}

}
