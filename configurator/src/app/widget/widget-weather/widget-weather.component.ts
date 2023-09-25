import { EntityTypes } from './../../entity/entity-types';
import { Weather } from './../../entity/weather';
import { AppService } from './../../app.service';
import { Component, OnInit } from '@angular/core';

@Component({
	selector: 'widget-weather',
	templateUrl: './widget-weather.component.html'
})
export class WidgetWeatherComponent implements OnInit {

	weather: Weather;

	constructor(public app: AppService) { }

	ngOnInit() {
		this.weather = this.app.entityManager.get<Weather>(EntityTypes.Weather, '1');
	}

}
