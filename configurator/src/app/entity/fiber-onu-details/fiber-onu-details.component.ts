import { FiberONU } from './../fiber-onu';
import { ScreenService } from './../../screen/screen.service';
import { Component, OnInit, Input } from '@angular/core';
import { EntityDetailComponent } from '../../entity-decorators';

@Component({
	selector: 'app-fiber-onu-details',
	templateUrl: './fiber-onu-details.component.html'
})
@EntityDetailComponent(FiberONU)
export class FiberONUDetailsComponent implements OnInit {

	@Input() entity: FiberONU = null;

	constructor(public screen: ScreenService) { }

	ngOnInit() {
		if (!this.entity) this.entity = this.screen.detailEntity as FiberONU;
	}

}
