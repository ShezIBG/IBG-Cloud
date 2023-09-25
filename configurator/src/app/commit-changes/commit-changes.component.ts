import { EntityChanges } from './../entity/entity-changes';
import { AppService } from './../app.service';
import { Component } from '@angular/core';

@Component({
	selector: 'commit-changes',
	templateUrl: './commit-changes.component.html'
})
export class CommitChangesComponent {

	changes: EntityChanges;

	expanded = {
		deleted: [],
		modified: [],
		added: []
	};

	constructor(public app: AppService) {
		this.changes = app.entityManager.getChanges();
	}

	isExpanded(group: string, type: string) {
		const array = this.expanded[group];
		if (!array) return false;
		return array.indexOf(type) !== -1;
	}

	toggle(group: string, type: string) {
		const array = this.expanded[group];
		if (!array) return;

		const index = array.indexOf(type);
		if (index === -1) {
			array.push(type);
		} else {
			array.splice(index, 1);
		}
	}

}
