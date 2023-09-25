import { Component, AfterViewInit, OnDestroy, Input, ViewChild } from '@angular/core';

declare var Skycons: any;

@Component({
	selector: 'weather-icon',
	templateUrl: './weather-icon.component.html'
})
export class WeatherIconComponent implements AfterViewInit, OnDestroy {

	static skycons;

	@ViewChild('canvas') canvas;

	@Input() type;
	@Input() width = 100;
	@Input() height = 100;

	constructor() {
		if (!WeatherIconComponent.skycons) {
			WeatherIconComponent.skycons = new Skycons(
				{ 'color': '#3bafda' },
				{ 'resizeClear': true }
			);
			WeatherIconComponent.skycons.play();
		}
	}

	ngAfterViewInit() {
		WeatherIconComponent.skycons.add(this.canvas.nativeElement, this.type);
	}

	ngOnDestroy() {
		WeatherIconComponent.skycons.remove(this.canvas.nativeElement);
	}

}
