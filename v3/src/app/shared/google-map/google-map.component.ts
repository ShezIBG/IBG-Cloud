/// <reference types="@types/googlemaps" />

import { Component, Input, EventEmitter, Output, ViewChild, OnInit, ElementRef, NgZone } from '@angular/core';
import { FormControl } from '@angular/forms';
import { AgmMarker, MapsAPILoader } from '@agm/core';

export class GoogleMapLocation {
	constructor(public lat, public lng) {
		this.lat = parseFloat(lat) || 0;
		this.lng = parseFloat(lng) || 0;
	}
}

@Component({
	selector: 'app-google-map',
	templateUrl: './google-map.component.html'
})
export class GoogleMapComponent implements OnInit {

	@ViewChild(AgmMarker) marker: AgmMarker;
	@ViewChild('search') searchElementRef: ElementRef;

	@Input() lat: any = 0;
	@Input() lng: any = 0;
	@Input() mapHeight = '300px';

	@Output() locationChanged = new EventEmitter<GoogleMapLocation>();

	locationObject: GoogleMapLocation;
	searchControl: FormControl;

	constructor(
		private mapsAPILoader: MapsAPILoader,
		private ngZone: NgZone
	) { }

	ngOnInit() {
		this.lat = parseFloat(this.lat) || 0;
		this.lng = parseFloat(this.lng) || 0;

		this.searchControl = new FormControl();

		this.mapsAPILoader.load().then(() => {
			const autocomplete = new google.maps.places.Autocomplete(this.searchElementRef.nativeElement, {});

			autocomplete.addListener('place_changed', () => {
				this.ngZone.run(() => {
					const place: google.maps.places.PlaceResult = autocomplete.getPlace();

					if (place.geometry === undefined || place.geometry === null) {
						return;
					}

					this.lat = place.geometry.location.lat();
					this.lng = place.geometry.location.lng();
					this.locationChanged.emit(new GoogleMapLocation(this.lat, this.lng));
				});
			});
		});
	}

	dragEnd(event) {
		this.locationChanged.emit(new GoogleMapLocation(event.coords.lat, event.coords.lng));
	}

}
