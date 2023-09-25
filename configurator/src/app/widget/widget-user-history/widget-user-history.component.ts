import { EntityTypes } from './../../entity/entity-types';
import { AppService } from './../../app.service';
import { ConfiguratorHistory } from './../../entity/configurator-history';
import { Component, OnInit } from '@angular/core';

@Component({
	selector: 'widget-user-history',
	templateUrl: './widget-user-history.component.html',
	styleUrls: ['./widget-user-history.component.css']
})
export class WidgetUserHistoryComponent implements OnInit {

	history: ConfiguratorHistory[] = [];

	constructor(public app: AppService) { }

	ngOnInit() {
		this.history = this.app.entityManager.find<ConfiguratorHistory>(EntityTypes.ConfiguratorHistory);
	}

}
