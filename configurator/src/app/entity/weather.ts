import { EntityTypes } from './entity-types';
import { Entity } from './entity';

export class Weather extends Entity {

	static type = EntityTypes.Weather;
	static groupName = 'Weather Data';

	getTypeDescription() { return 'Weather'; }
	getIconClass() { return 'md md-cloud'; }
	getSort() { return ['Weather Data']; }

	canDelete() {
		return false;
	}

}
