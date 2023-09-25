import { ScreenService } from './../screen/screen.service';
import { Entity } from './../entity/entity';
import { Component, Input } from '@angular/core';

@Component({
	selector: 'app-floorplan-info',
	templateUrl: './floorplan-info.component.html'
})
export class FloorplanInfoComponent {

	@Input() entity: Entity;

	constructor(public screen: ScreenService) { }

	reloadArea() {
		const area = this.entity;
		this.screen.selectTreeEntity(null);
		setTimeout(() => { this.screen.selectTreeEntity(area) }, 0);
	}

}
