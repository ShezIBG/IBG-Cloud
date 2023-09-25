import { EntityTypes } from './../entity-types';
import { EntitySortPipe } from './../entity-sort.pipe';
import { Breaker } from './../breaker';
import { DistBoard } from './../distboard';
import { Entity } from './../entity';
import { Area } from './../area';
import { ModalEvent } from './../../modal/modal.component';
import { ModalService } from './../../modal/modal.service';
import { Component } from '@angular/core';
import { EntityNewComponent } from '../../entity-decorators';

@Component({
	selector: 'app-distboard-virtual-new',
	templateUrl: './distboard-virtual-new.component.html'
})
@EntityNewComponent(DistBoard)
export class DistboardVirtualNewComponent {

	area: Area;
	entity: Entity;

	incoming = true;
	distboard = null;
	distboardList = [];
	locationList = ['L1', 'L2', 'L3', 'L1,2,3'];

	constructor(public modalService: ModalService) {
		this.area = this.modalService.data.area;
		this.entity = this.modalService.data.entity;

		this.distboardList = EntitySortPipe.transform(this.area.entityManager.find<DistBoard>(EntityTypes.DistBoard, { ways: { $gt: 0 } }));
		this.distboard = this.distboardList[0];
	}

	getDBDescription(db: DistBoard) {
		const description = [db.getDescription() || ''];

		let entity;

		entity = db.closest(EntityTypes.Area);
		if (entity) description.unshift(entity.getDescription() || '');

		entity = db.closest(EntityTypes.Floor);
		if (entity) description.unshift(entity.getDescription() || '');

		return description.join(' / ');
	}

	getBreakers() {
		const breakers = this.distboard ? this.area.entityManager.find<Breaker>(EntityTypes.Breaker, { db_id: this.distboard.data.id }) : [];
		return EntitySortPipe.transform(
			breakers.filter(breaker => {
				return !breaker.getFeedDB();
			})
		);
	}

	modalHandler(event: ModalEvent) {
		if (event.type === 'button' && event.data.name === 'OK') {
			if (this.incoming) {
				this.entity.data.feed_breaker_id = null;
				this.entity.data.board_type = this.entity.data.location === 'L1,2,3' ? 3 : 12;
				this.entity.data.ways = 1;
			} else {
				if (!this.entity.data.feed_breaker_id) return;

				// Non-incoming virtual DBs have no ways
				this.entity.data.ways = 0;

				// Copy details from feed breaker
				const breaker = this.area.entityManager.get<Breaker>(EntityTypes.Breaker, this.entity.data.feed_breaker_id);
				this.entity.data.board_type = breaker.data.board_type;
				this.entity.data.location = breaker.data.location;
			}

			const vdb = this.entity.copyToArea(this.area);
			if (this.incoming) {
				// Create virtual breaker
				this.area.entityManager.createEntity({
					entity: 'breaker',
					db_id: vdb.data.id,
					breaker_num: 1,
					board_type: vdb.data.board_type,
					way: 1,
					location: vdb.data.location,
					short_description: Breaker.generateShortDescription(vdb.data.board_type, 1, vdb.data.location),
					long_description: 'Incoming Supply ' + vdb.data.location
				});
			}

			event.modal.close();
		} else {
			event.modal.close();
		}
	}

}
