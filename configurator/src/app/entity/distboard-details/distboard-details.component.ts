import { EntityTypes } from './../entity-types';
import { EntitySortPipe } from './../entity-sort.pipe';
import { DistBoard } from './../distboard';
import { ScreenService } from './../../screen/screen.service';
import { AppService } from './../../app.service';
import { Breaker } from './../breaker';
import { Component, Input, OnInit } from '@angular/core';
import { EntityDetailComponent } from '../../entity-decorators';

@Component({
	selector: 'app-distboard-details',
	templateUrl: './distboard-details.component.html'
})
@EntityDetailComponent(DistBoard)
export class DistboardDetailsComponent implements OnInit {

	@Input() entity: DistBoard = null;

	hovered = null;
	distboard = null;
	distboardList = [];

	constructor(public app: AppService, public screen: ScreenService) { }

	ngOnInit() {
		if (!this.entity) this.entity = this.screen.detailEntity as DistBoard;

		// Get possible feed DB list
		// Remove the ones that would result in a reference loop
		this.distboardList = EntitySortPipe.transform(this.entity.entityManager.find<DistBoard>(EntityTypes.DistBoard, { $ne: this.entity, ways: { $gt: 0 } }));
		this.distboardList = this.distboardList.filter(db => {
			return db.getFeedDBChain().indexOf(this.entity) === -1;
		});

		this.distboard = null;

		const feedBreaker = this.entity.getFeedBreaker();
		if (feedBreaker) {
			this.distboard = feedBreaker.closest(EntityTypes.DistBoard) || null;
		}
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

	getFeedBreakerList() {
		if (!this.entity || !this.distboard) return [];

		const breakers = this.entity.entityManager.find<Breaker>(EntityTypes.Breaker, { db_id: this.distboard.data.id, location: this.entity.data.location });
		return EntitySortPipe.transform(
			breakers.filter(breaker => {
				const feedDB = breaker.getFeedDB();
				return !feedDB || feedDB === this.entity;
			})
		);
	}

	addBreaker(way, location, boardType) {
		this.app.modal.open(Breaker.newComponents[0], {
			distboard: this.entity,
			way: way,
			location: location,
			board_type: boardType
		});
	}

	editBreaker(breaker: Breaker) {
		this.app.modal.open(Breaker.detailComponents[0], { entity: breaker });
	}

	getFeedBreakerDescription() {
		const breaker = this.entity.getFeedBreaker();
		if (!breaker) return '';

		let entity, desc = '';
		desc = breaker.getDescription();

		entity = breaker.closest(EntityTypes.DistBoard);
		if (!entity) return desc;
		desc = entity.getDescription() + ' / ' + desc;

		entity = breaker.closest(EntityTypes.Area);
		if (!entity) return desc;
		desc = entity.getDescription() + ' / ' + desc;

		entity = breaker.closest(EntityTypes.Floor);
		if (!entity) return desc;
		desc = entity.getDescription() + ' / ' + desc;

		return desc;
	}

	jumpToFeedDB(breaker: Breaker) {
		const feedDB = breaker.getFeedDB();
		if (feedDB) feedDB.jumpTo(this.app);
	}

	jumpToFeedBreaker() {
		const breaker = this.entity.getFeedBreaker();
		if (breaker) breaker.jumpTo(this.app);
	}

}
