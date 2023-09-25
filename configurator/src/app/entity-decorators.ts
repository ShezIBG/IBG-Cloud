import { Entity } from './entity/entity';

export function EntityTreeComponent(cls) {
	return function (target) {
		// Make sure class has its own static array
		if (cls.treeComponents === Entity.treeComponents) cls.treeComponents = [];

		if (cls.treeComponents.indexOf(target) === -1) {
			cls.treeComponents.push(target);
		}
	};
}

export function EntityDetailComponent(cls) {
	return function (target) {
		// Make sure class has its own static array
		if (cls.detailComponents === Entity.detailComponents) cls.detailComponents = [];

		if (cls.detailComponents.indexOf(target) === -1) {
			cls.detailComponents.push(target);
		}
	};
}

export function EntityNewComponent(cls) {
	return function (target) {
		// Make sure class has its own static array
		if (cls.newComponents === Entity.newComponents) cls.newComponents = [];

		if (cls.newComponents.indexOf(target) === -1) {
			cls.newComponents.push(target);
		}
	};
}

export function EntityAssignComponent(cls) {
	return function (target) {
		// Make sure class has its own static array
		if (cls.assignComponents === Entity.assignComponents) cls.assignComponents = [];

		if (cls.assignComponents.indexOf(target) === -1) {
			cls.assignComponents.push(target);
		}
	};
}
