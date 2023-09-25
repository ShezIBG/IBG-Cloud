import { AppService } from './../../app.service';
import { ScreenService } from './../../screen/screen.service';
import { FiberOLT } from './../fiber-olt';
import { Component, OnInit, Input } from '@angular/core';
import { EntityDetailComponent } from '../../entity-decorators';

@Component({
	selector: 'app-fiber-olt-details',
	templateUrl: './fiber-olt-details.component.html'
})
@EntityDetailComponent(FiberOLT)
export class FiberOLTDetailsComponent implements OnInit {

	@Input() entity: FiberOLT = null;

	constructor(
		public app: AppService,
		public screen: ScreenService
	) { }

	ngOnInit() {
		if (!this.entity) this.entity = this.screen.detailEntity as FiberOLT;
	}

}
