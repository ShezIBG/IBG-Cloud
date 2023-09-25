import { FiberONU } from './../fiber-onu';
import { ScreenService } from './../../screen/screen.service';
import { AppService } from './../../app.service';
import { FiberOLT } from './../fiber-olt';
import { Component, OnInit, Input } from '@angular/core';
import { EntityAssignComponent } from '../../entity-decorators';

@Component({
	selector: 'app-fiber-olt-assign',
	templateUrl: './fiber-olt-assign.component.html'
})
@EntityAssignComponent(FiberOLT)
export class FiberOLTAssignComponent implements OnInit {

	@Input() entity: FiberOLT = null;

	hovered = null;

	constructor(public app: AppService, public screen: ScreenService) { }

	ngOnInit() {
		if (!this.entity) this.entity = this.screen.treeEntity as FiberOLT;
	}

	assignAll() {
		this.screen.assignables.forEach((onu: FiberONU) => {
			onu.assignTo(this.entity);
		});
		this.screen.clearAssignables();
	}

}
