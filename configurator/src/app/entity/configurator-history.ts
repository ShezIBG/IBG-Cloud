import { EntityTypes } from './entity-types';
import { EntityManager } from './entity-manager';
import { User } from './user';
import { Entity } from './entity';

export class ConfiguratorHistory extends Entity {

	static type = EntityTypes.ConfiguratorHistory;
	static groupName = 'History';

	changeCubes = [];

	constructor(data, public entityManager: EntityManager) {
		super(data, entityManager);
		this.refreshChangeCubes();
	}

	getTypeDescription() { return 'History'; }
	getIconClass() { return 'md md-history'; }
	getSort() { return [this.data.update_date]; }

	canDelete() {
		return false;
	}

	getUser() {
		return this.entityManager.get<User>(EntityTypes.User, this.data.user_id);
	}

	getName() {
		const user = this.getUser();
		return user ? user.data.name : '';
	}

	getUserEmail() {
		const user = this.getUser();
		return user ? user.data.email_addr : '';
	}

	private refreshChangeCubes() {
		const result = [];

		let a = this.data.count_added;
		let m = this.data.count_modified;
		let d = this.data.count_deleted;
		let i = 0;

		const max = 5;
		const total = a + m + d;

		if (total > max) {
			let na, nm, nd;
			na = Math.round((a / total) * max);
			nm = Math.round((m / total) * max);
			nd = Math.round((d / total) * max);
			if (a > 0 && na === 0) na = 1;
			if (m > 0 && nm === 0) nm = 1;
			if (d > 0 && nd === 0) nd = 1;
			while (na + nm + nd !== max) {
				if (na + nm + nd > max) {
					// Take one off
					if (nd >= nm && nd >= na) {
						nd -= 1;
					} else if (nm >= na && nm >= nd) {
						nm -= 1;
					} else {
						na -= 1;
					}
				} else {
					// Add one
					if (nd <= nm && nd <= na) {
						nd += 1;
					} else if (nm <= na && nm <= nd) {
						nm += 1;
					} else {
						na += 1;
					}
				}
			}
			a = na;
			m = nm;
			d = nd;
		}

		for (i = 0; i < a; i++) result.push({ cl: 'change-added', title: 'Added: ' + this.data.count_added });
		for (i = 0; i < m; i++) result.push({ cl: 'change-modified', title: 'Modified: ' + this.data.count_modified });
		for (i = 0; i < d; i++) result.push({ cl: 'change-deleted', title: 'Deleted: ' + this.data.count_deleted });
		for (i = 0; i < (max - a - m - d); i++) result.push({ cl: 'change-none', title: '' });

		this.changeCubes = result;
	}

}
