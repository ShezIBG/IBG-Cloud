import { Router } from './../router';
import { ScreenService } from './../../screen/screen.service';
import { Component, Input, OnInit } from '@angular/core';
import { EntityDetailComponent } from '../../entity-decorators';

@Component({
	selector: 'app-router-details',
	templateUrl: './router-details.component.html'
})
@EntityDetailComponent(Router)
export class RouterDetailsComponent implements OnInit {

	@Input() entity: Router = null;

	constructor(public screen: ScreenService) { }

	ngOnInit() {
		if (!this.entity) this.entity = this.screen.detailEntity as Router;
	}

}
