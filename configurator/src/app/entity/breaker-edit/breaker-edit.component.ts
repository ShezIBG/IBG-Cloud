import { AppService } from './../../app.service';
import { ModalEvent } from './../../modal/modal.component';
import { ModalService } from './../../modal/modal.service';
import { Breaker } from './../breaker';
import { Component } from '@angular/core';
import { EntityDetailComponent } from '../../entity-decorators';

@Component({
	selector: 'app-breaker-edit',
	templateUrl: './breaker-edit.component.html'
})
@EntityDetailComponent(Breaker)
export class BreakerEditComponent {

	breaker: Breaker;
	data: any = {};

	constructor(public app: AppService, public modalService: ModalService) {
		this.breaker = modalService.data.entity;
		this.data.long_description = this.breaker.data.long_description;
		this.data.amp_rating = this.breaker.data.amp_rating;
	}

	modalHandler(event: ModalEvent) {
		if (event.type === 'button' && event.data.name === 'OK') {
			this.breaker.data.long_description = this.data.long_description;
			this.breaker.data.amp_rating = this.data.amp_rating;
		}
		event.modal.close();
	}

}
