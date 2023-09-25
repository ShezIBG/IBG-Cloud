import { EntityTypes } from './entity-types';
import { Breaker } from './breaker';
import { EntityManager } from './entity-manager';
import { Area } from './area';
import { Entity, MovableEntity } from './entity';

declare var Mangler: any;

export class DistBoard extends Entity implements MovableEntity {

	static type = EntityTypes.DistBoard;
	static groupName = 'Distribution Boards';

	l = 0;
	ways = [];

	// The ways array is an array of arrays, the first index is the way. If board is single phase or the breaker is three phase, item is an array of 1 breaker.
	// Otherwise items are an array of up to 3 breakers at L1, L2 and L3 respectively.

	constructor(data, entityManager: EntityManager) {
		super(data, entityManager);

		// Create ways items
		const cnt = this.data.ways || 0;
		for (let i = 0; i < cnt; i++) this.ways[i] = [];
		this.refreshBoardType();
		this.refreshWays();

		// Subscribe to its own event to refresh
		this.onItemAddedEvent.subscribe(entity => this.refreshWays());
		this.onItemRemovedEvent.subscribe(entity => this.refreshWays());
	}

	getTypeDescription() {
		if (this.data.is_virtual) return 'Virtual DB';

		switch (this.data.device_type) {
			case 'switch_board': return 'LV Switch Board';
			case 'mccb_board': return 'MCCB Board';
			case 'bus_bar': return 'Bus Bar';
			default: return 'Distribution Board';
		}
	}

	getIconClass() { return this.data.is_virtual ? 'md md-crop-free' : 'md md-crop-din'; }
	getParent() { return this.entityManager.get<Area>(EntityTypes.Area, this.data.area_id); }
	getSort() { return [this.data.area_id, this.data.display_order]; }
	getTags() { return ['structure']; }

	getNewComponent() {
		return this.data.is_virtual ? super.getNewComponent() : null;
	}

	canDelete() {
		if (this.data.is_virtual) {
			// Skip items check, but DO check if virtual breakers are unassigned
			let ok = true;
			this.items.forEach((breaker: Breaker) => {
				if (ok && breaker.assigned.length) ok = false;
			});
			return ok;
		}
		return super.canDelete();
	}

	beforeDelete() {
		// Virtual DB can be deleted while it still has breakers. Delete them.
		this.items.slice().forEach(item => this.entityManager.deleteEntity(item));
	}

	copyToArea(area: Area) {
		const data = Mangler.clone(this.data);
		data.id = area.entityManager.getAutoId();
		data.area_id = area.data.id;
		data.display_order = area.entityManager.getAutoDisplayOrder();
		return area.createEntity(data);
	}

	canMove() { return true; }

	moveToArea(area: Area) {
		if (area.entityManager === this.entityManager) {
			this.data.area_id = area.data.id;
			this.data.display_order = area.entityManager.getAutoDisplayOrder();
			this.refresh();
			return true;
		}
		return false;
	}

	refreshWays() {
		// Clear ways
		this.ways.forEach(way => {
			if (this.is3P()) {
				way[0] = way[1] = way[2] = null;
			} else {
				way.length = 1;
				way[0] = null;
			}
		});

		// Add breakers
		this.items.forEach(entity => {
			if (EntityTypes.isBreaker(entity)) {
				const way = entity.data.way || 1;

				// Make sure we have enough way to fit the breaker
				while (this.data.ways < way) {
					this.addWay();
				}

				const breakerPhases = entity.getPhaseList();
				if (breakerPhases.length === 3 || !this.is3P()) {
					this.ways[way - 1].length = 0;
					this.ways[way - 1][0] = entity;
				} else {
					breakerPhases.forEach(phase => {
						this.ways[way - 1][phase - 1] = entity;
					});
				}
			}
		});
	}

	addWay() {
		this.ways.push(this.is3P() ? [null, null, null] : [null]);
		this.data.ways++;
	}

	removeWay() {
		if (!this.ways.length) return;

		// Only remove last way if it's empty
		if (this.isLastWayEmpty()) {
			this.data.ways--;
			this.ways.pop();
		}
	}

	refreshBoardType() {
		switch (this.data.location) {
			case 'L1': this.l = 1; break;
			case 'L2': this.l = 2; break;
			case 'L3': this.l = 3; break;
			default: this.l = 1; break;
		}
	}

	canUpdateLocation() {
		// You can only change location of 1P boards
		if (this.is3P()) return false;

		// You cannot change location if any of the breakers are assigned or they're
		// feeding a non-virtual DB
		let ok = true;
		this.items.forEach(entity => {
			if (EntityTypes.isBreaker(entity)) {
				if (entity.assigned.length) ok = false;

				const feedDB = entity.getFeedDB();
				if (feedDB && !feedDB.isVirtual()) ok = false;
			}
		});
		return ok;
	}

	updateLocation(location) {
		// Cannot change 3 phase DB
		if (!this.canUpdateLocation()) return false;

		// Check input
		let l = 0;
		switch (location) {
			case 'L1': l = 1; break;
			case 'L2': l = 2; break;
			case 'L3': l = 3; break;
			default: return;
		}

		// Update DB
		this.data.location = location;

		// Update breakers
		this.items.forEach(breaker => {
			if (EntityTypes.isBreaker(breaker)) {
				breaker.data.location = location;
				breaker.updateShortDescription(breaker.data.board_type, breaker.data.way, l);

				const vdb = breaker.getVirtualDB();
				if (vdb) vdb.updateLocation(location);
			}
		});

		// Remove feed breaker
		if (!this.isVirtual()) this.data.feed_breaker_id = null;

		// Refresh DB ways
		this.refreshBoardType();
		this.refreshWays();
	}

	isLastWayEmpty() {
		if (!this.ways.length) return false;
		const last = this.ways[this.ways.length - 1];
		return !last[0] && !last[1] && !last[2];
	}

	isVirtual() {
		return !!this.data.is_virtual;
	}

	hasBreakers() {
		let ok = false;
		this.items.forEach(entity => {
			if (EntityTypes.isBreaker(entity)) ok = true;
		});
		return ok;
	}

	is3P() {
		return this.data.board_type === 3;
	}

	getPrevSibling() {
		let prev = null;
		let sort = 0;
		this.getParent().items.forEach(item => {
			if (item.type === this.type && item.data.display_order > sort && item.data.display_order < this.data.display_order) {
				prev = item;
				sort = item.data.display_order;
			}
		});
		return prev;
	}

	getNextSibling() {
		let next = null;
		let sort = Number.MAX_SAFE_INTEGER;
		this.getParent().items.forEach(item => {
			if (item.type === this.type && item.data.display_order < sort && item.data.display_order > this.data.display_order) {
				next = item;
				sort = item.data.display_order;
			}
		});
		return next;
	}

	moveUp() {
		const prev = this.getPrevSibling();
		const temp = this.data.display_order;
		this.data.display_order = prev.data.display_order;
		prev.data.display_order = temp;

		const parent = this.getParent();
		if (parent) parent.items = parent.items.slice();
	}

	moveDown() {
		const prev = this.getNextSibling();
		const temp = this.data.display_order;
		this.data.display_order = prev.data.display_order;
		prev.data.display_order = temp;
		this.refresh();

		const parent = this.getParent();
		if (parent) parent.items = parent.items.slice();
	}

	getFeedBreaker() {
		return this.entityManager.get<Breaker>(EntityTypes.Breaker, this.data.feed_breaker_id);
	}

	getFeedDB() {
		const feedBreaker = this.getFeedBreaker();
		if (feedBreaker) return feedBreaker.closest(EntityTypes.DistBoard);
		return null;
	}

	getFeedDBChain() {
		const chain = [];
		let db;

		db = this.getFeedBreaker();
		while (db) {
			chain.push(db);
			db = db.getFeedDB();
		}

		return chain;
	}

}
