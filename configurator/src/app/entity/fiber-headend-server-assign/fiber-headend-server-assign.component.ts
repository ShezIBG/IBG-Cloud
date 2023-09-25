import { FiberOLT } from './../fiber-olt';
import { ScreenService } from './../../screen/screen.service';
import { AppService } from './../../app.service';
import { FiberHeadendServer } from './../fiber-headend-server';
import { Component, OnInit, Input } from '@angular/core';
import { EntityAssignComponent } from '../../entity-decorators';

@Component({
	selector: 'app-fiber-headend-server-assign',
	templateUrl: './fiber-headend-server-assign.component.html'
})
@EntityAssignComponent(FiberHeadendServer)
export class FiberHeadendServerAssignComponent implements OnInit {

	@Input() entity: FiberHeadendServer = null;

	hovered = null;

	constructor(public app: AppService, public screen: ScreenService) { }

	ngOnInit() {
		if (!this.entity) this.entity = this.screen.treeEntity as FiberHeadendServer;
	}

	assignAll() {
		this.screen.assignables.forEach((olt: FiberOLT) => {
			olt.assignTo(this.entity);
		});
		this.screen.clearAssignables();
	}

}
