import { EntityTypes } from './entity-types';
import { CT } from './ct';
import { DistBoard } from './distboard';
import { Entity } from './entity';

export class Breaker extends Entity {

	static type = EntityTypes.Breaker;
	static groupName = 'Breakers';

	static generateLocationDescription(boardType: number, location: number) {
		return boardType === 3 ? 'L1,2,3' : 'L' + location;
	}

	static generateShortDescription(boardType: number, way: number, location: number | string) {
		if (typeof location === 'string') {
			switch (location) {
				case 'L1': location = 1; break;
				case 'L2': location = 2; break;
				case 'L3': location = 3; break;
				case 'L1,2,3': location = 1; break;
				default: location = 1; break;
			}
		}
		return (boardType === 3 ? '3P' : '1P') + ' W' + way + this.generateLocationDescription(boardType, location);
	}

	getTypeDescription() { return 'Breaker'; }
	getIconClass() { return 'md md-label'; }
	getDescription() { return this.data.short_description + ' - ' + this.data.long_description; }
	getLongDescription() { return this.data.long_description; }
	getParent() { return this.entityManager.get<DistBoard>(EntityTypes.DistBoard, this.data.db_id); }
	getSort() { return [this.data.way, this.data.location]; }
	getTags() { return ['structure']; }

	canDelete() {
		if (this.getFeedDB()) return false;
		return super.canDelete();
	}

	isUnassigned() {
		let unassigned = true;
		this.assigned.forEach(entity => {
			if (EntityTypes.isCT(entity)) unassigned = false;
		});
		return unassigned;
	}

	isAssignableTo(entity: Entity) {
		// ABBMeter and PM12 are just abstractions from the treeview's point of view
		return EntityTypes.isABBMeter(entity) || EntityTypes.isPM12(entity) || EntityTypes.isCT(entity);
	}

	isAssignedTo(entity: Entity) {
		if (EntityTypes.isABBMeter(entity) || EntityTypes.isPM12(entity)) {
			let ret = false;
			entity.items.forEach(item => {
				if (EntityTypes.isCT(item) && item.isAssignedTo(this)) ret = true;
			});
			return ret;
		} else if (EntityTypes.isCT(entity)) {
			return this.assigned.indexOf(entity) !== -1;
		} else {
			return this.getAssignedTo().indexOf(entity) !== -1;
		}
	}

	getAssignedToType(entity: Entity) {
		let ret = null;
		if (EntityTypes.isABBMeter(entity) || EntityTypes.isPM12(entity)) {
			this.assigned.forEach((assignedTo: Entity) => {
				if (EntityTypes.isCT(assignedTo)) ret = assignedTo;
			});
		} else {
			this.getAssignedTo().forEach((assignedTo: Entity) => {
				if (assignedTo.type === entity.type) ret = assignedTo;
			});
		}
		return ret;
	}

	getAssignedToInfo(entity: Entity) {
		if (!this.isAssignedTo(entity)) return '';
		if (EntityTypes.isCT(entity)) {
			const parent = entity.getParent();
			if (!parent || EntityTypes.isABBMeter(parent)) {
				return entity.getDescription();
			} else {
				return entity.getParent().getDescription() + ' / ' + entity.getDescription();
			}
		}
		return entity.getDescription();
	}

	getPhaseList(): number[] {
		switch (this.data.location) {
			case 'L1': return [1];
			case 'L2': return [2];
			case 'L3': return [3];
			case 'L1,2,3': return [1, 2, 3];
			default: return [];
		}
	}

	is3P() {
		return this.data.board_type === 3;
	}

	getVirtualDB(): DistBoard {
		return this.entityManager.findOne<DistBoard>(EntityTypes.DistBoard, { feed_breaker_id: this.data.id, is_virtual: 1 });
	}

	getFeedDB(): DistBoard {
		return this.entityManager.findOne<DistBoard>(EntityTypes.DistBoard, { feed_breaker_id: this.data.id });
	}

	getCT(): CT {
		let ct: CT = null;
		this.assigned.forEach(entity => {
			if (EntityTypes.isCT(entity)) ct = entity;
		});
		return ct.getMainCTFromGroup();
	}

	updateShortDescription(boardType: number, way: number, location: number | string) {
		this.data.short_description = Breaker.generateShortDescription(boardType, way, location);
	}

}
