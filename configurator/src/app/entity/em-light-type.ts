import { EntityTypes } from './entity-types';
import { Entity } from './entity';

export class EmLightType extends Entity {

	static type = EntityTypes.EmLightType;
	static groupName = 'Emergency Light Types';

	getTypeDescription() { return 'Emergency Light Type'; }
	getIconClass() { return 'md md-info-outline'; }
	getParent() { return null; }
	getSort() { return [this.data.description]; }
	getTags() { return []; }

	canDelete() { return false; }

}
