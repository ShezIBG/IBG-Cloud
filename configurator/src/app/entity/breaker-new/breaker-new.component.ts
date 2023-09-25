import { AppService } from './../../app.service';
import { Breaker } from './../breaker';
import { ModalEvent } from './../../modal/modal.component';
import { ModalService } from './../../modal/modal.service';
import { EntityManager } from './../entity-manager';
import { DistBoard } from './../distboard';
import { Component } from '@angular/core';
import { EntityNewComponent } from '../../entity-decorators';

@Component({
	selector: 'app-breaker-new',
	templateUrl: './breaker-new.component.html'
})
@EntityNewComponent(Breaker)
export class BreakerNewComponent {

	distboard: DistBoard;
	entityManager: EntityManager;
	data: any;

	constructor(public app: AppService, public modalService: ModalService) {
		this.distboard = modalService.data.distboard;
		const way = this.modalService.data.way;
		const location = this.modalService.data.location;
		const board_type = this.modalService.data.board_type;

		this.entityManager = this.distboard.entityManager;

		this.data = {
			entity: 'breaker',
			id: this.entityManager.getAutoId(),
			db_id: this.distboard.data.id,
			board_type: board_type,
			way: way,
			location: Breaker.generateLocationDescription(board_type, location),
			short_description: Breaker.generateShortDescription(board_type, way, location),
			long_description: '',
			amp_rating: null
		};
	}

	modalHandler(event: ModalEvent) {
		if (event.type === 'button' && event.data.name === 'OK') {
			this.entityManager.createEntity(this.data);
		}
		event.modal.close();
	}

}
