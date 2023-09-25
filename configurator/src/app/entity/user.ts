import { EntityTypes } from './entity-types';
import { Entity } from './entity';

export class User extends Entity {

	static type = EntityTypes.User;
	static groupName = 'Users';

	getTypeDescription() { return 'User'; }
	getIconClass() { return 'md md-person'; }
	getSort() { return [this.data.name]; }

	canDelete() {
		return false;
	}

}
