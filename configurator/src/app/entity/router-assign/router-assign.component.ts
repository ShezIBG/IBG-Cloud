import { Gateway } from './../gateway';
import { ScreenService } from './../../screen/screen.service';
import { AppService } from './../../app.service';
import { Router } from './../router';
import { Component, OnInit, Input } from '@angular/core';
import { EntityAssignComponent } from '../../entity-decorators';

@Component({
	selector: 'app-router-assign',
	templateUrl: './router-assign.component.html'
})
@EntityAssignComponent(Router)
export class RouterAssignComponent implements OnInit {

	@Input() entity: Router = null;

	hovered = null;

	constructor(public app: AppService, public screen: ScreenService) { }

	ngOnInit() {
		if (!this.entity) this.entity = this.screen.treeEntity as Router;
	}

	assignAll() {
		this.screen.assignables.forEach((gateway: Gateway) => {
			gateway.assignTo(this.entity);
		});
		this.screen.clearAssignables();
	}

}
