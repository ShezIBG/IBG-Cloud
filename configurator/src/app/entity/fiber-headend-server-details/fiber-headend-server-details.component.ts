import { ScreenService } from './../../screen/screen.service';
import { FiberHeadendServer } from './../fiber-headend-server';
import { Component, OnInit, Input } from '@angular/core';
import { EntityDetailComponent } from '../../entity-decorators';

@Component({
	selector: 'app-fiber-headend-server-details',
	templateUrl: './fiber-headend-server-details.component.html'
})
@EntityDetailComponent(FiberHeadendServer)
export class FiberHeadendServerDetailsComponent implements OnInit {

	@Input() entity: FiberHeadendServer = null;

	constructor(public screen: ScreenService) { }

	ngOnInit() {
		if (!this.entity) this.entity = this.screen.detailEntity as FiberHeadendServer;
	}

}
