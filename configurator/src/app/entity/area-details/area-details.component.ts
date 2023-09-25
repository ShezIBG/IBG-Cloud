import { Area } from './../area';
import { ScreenService } from './../../screen/screen.service';
import { Component, Input, OnInit } from '@angular/core';
import { EntityDetailComponent } from '../../entity-decorators';

@Component({
	selector: 'app-area-details',
	templateUrl: './area-details.component.html'
})
@EntityDetailComponent(Area)
export class AreaDetailsComponent implements OnInit {

	@Input() entity: Area = null;

	constructor(public screen: ScreenService) { }

	ngOnInit() {
		if (!this.entity) this.entity = this.screen.detailEntity as Area;
	}

}
