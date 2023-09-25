import { Floor } from './../floor';
import { ScreenService } from './../../screen/screen.service';
import { Component, OnInit, Input } from '@angular/core';
import { EntityTreeComponent } from '../../entity-decorators';

@Component({
	selector: 'app-floor-details',
	templateUrl: './floor-details.component.html'
})
@EntityTreeComponent(Floor)
export class FloorDetailsComponent implements OnInit {

	@Input() entity: Floor = null;

	constructor(public screen: ScreenService) { }

	ngOnInit() {
		if (!this.entity) this.entity = this.screen.treeEntity as Floor;
	}

}
