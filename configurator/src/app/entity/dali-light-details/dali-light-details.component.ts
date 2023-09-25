import { AppService } from './../../app.service';
import { EntityDetailComponent } from 'app/entity-decorators';
import { DaliLight } from './../dali-light';
import { Component, OnInit, Input } from '@angular/core';
import { ScreenService } from 'app/screen/screen.service';

@Component({
	selector: 'app-dali-light-details',
	templateUrl: './dali-light-details.component.html'
})
@EntityDetailComponent(DaliLight)
export class DaliLightDetailsComponent implements OnInit {

	@Input() entity: DaliLight = null;

	constructor(
		public app: AppService,
		public screen: ScreenService
	) { }

	ngOnInit() {
		if (!this.entity) this.entity = this.screen.detailEntity as DaliLight;
	}

}
