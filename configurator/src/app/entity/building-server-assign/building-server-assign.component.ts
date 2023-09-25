import { BuildingServer } from './../building-server';
import { ScreenService } from './../../screen/screen.service';
import { AppService } from './../../app.service';
import { Component, OnInit, Input } from '@angular/core';
import { EntityAssignComponent } from '../../entity-decorators';
import { Entity } from '../entity';

@Component({
	selector: 'app-building-server-assign',
	templateUrl: './building-server-assign.component.html'
})
@EntityAssignComponent(BuildingServer)
export class BuildingServerAssignComponent implements OnInit {

	@Input() entity: BuildingServer = null;

	hovered = null;

	constructor(
		public app: AppService,
		public screen: ScreenService
	) { }

	ngOnInit() {
		if (!this.entity) this.entity = this.screen.treeEntity as BuildingServer;
	}

	assignAll() {
		this.screen.assignables.forEach((assignable: Entity) => {
			assignable.assignTo(this.entity);
		});
		this.screen.clearAssignables();
	}

}
