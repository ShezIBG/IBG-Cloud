import { ScreenService } from './../../screen/screen.service';
import { EmLight } from './../em-light';
import { Component, OnInit, Input } from '@angular/core';
import { EntityDetailComponent } from '../../entity-decorators';

@Component({
	selector: 'app-em-light-details',
	templateUrl: './em-light-details.component.html'
})
@EntityDetailComponent(EmLight)
export class EmLightDetailsComponent implements OnInit {

	@Input() entity: EmLight = null;

	constructor(public screen: ScreenService) { }

	ngOnInit() {
		if (!this.entity) this.entity = this.screen.detailEntity as EmLight;
	}

}
