import { EntityTypes } from './entity-types';
import { Entity } from './entity';

export class UserContent extends Entity {

	static type = EntityTypes.UserContent;
	static groupName = 'User Content';

	getTypeDescription() { return 'User Content'; }
	getIconClass() { return 'md md-image'; }
	getParent() { return null; }
	getSort() { return [this.data.id]; }
	getTags() { return []; }

	getURL() {
		return this.data.generated_url;
	}

}
