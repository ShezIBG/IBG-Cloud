import { EntityTypes } from './../entity/entity-types';
import { AppService } from './../app.service';
import { ScreenService } from './screen.service';
import { Component, Input, OnInit } from '@angular/core';

@Component({
	selector: 'app-screen',
	templateUrl: './screen.component.html',
	providers: [ScreenService]
})
export class ScreenComponent implements OnInit {

	@Input() type: string;
	@Input() filter: string;

	constructor(public app: AppService, public screen: ScreenService) { }

	ngOnInit() {
		this.screen.type = this.type;
		this.screen.filter = this.filter;

		if (this.type === 'device' && this.filter === 'structure') this.app.structureScreenService = this.screen;
		if (this.type === 'device' && this.filter === 'equipment') this.app.equipmentScreenService = this.screen;
		if (this.type === 'assign') this.app.assignScreenService = this.screen;
		if (this.type === 'floorplan') this.app.floorplanScreenService = this.screen;
	}

	hasToolbox() {
		return EntityTypes.isArea(this.screen.treeEntity);
	}
}
