import { CoolPlug } from './../coolplug';
import { CoolHub } from './../coolhub';
import { ScreenService } from './../../screen/screen.service';
import { AppService } from './../../app.service';
import { Component, OnInit, Input } from '@angular/core';
import { EntityAssignComponent } from '../../entity-decorators';

@Component({
	selector: 'app-coolhub-assign',
	templateUrl: './coolhub-assign.component.html',
})
@EntityAssignComponent(CoolHub)
export class CoolHubAssignComponent implements OnInit {

	@Input() entity: CoolHub = null;

	hovered = null;

	constructor(
		public app: AppService,
		public screen: ScreenService
	) { }

	ngOnInit() {
		if (!this.entity) this.entity = this.screen.treeEntity as CoolHub;
	}

	assignAll() {
		this.screen.assignables.forEach((plug: CoolPlug) => {
			plug.assignTo(this.entity);
		});
		this.screen.clearAssignables();
	}

}
