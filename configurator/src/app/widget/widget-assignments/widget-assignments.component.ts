import { AppService } from './../../app.service';
import { Entity } from './../../entity/entity';
import { EntitySortPipe } from './../../entity/entity-sort.pipe';
import { Component, OnInit } from '@angular/core';

declare var Mangler: any;

@Component({
	selector: 'widget-assignments',
	templateUrl: './widget-assignments.component.html',
	styleUrls: ['./widget-assignments.component.css']
})
export class WidgetAssignmentsComponent implements OnInit {

	unassignedList = [];
	assignedList = [];
	tab = 'unassigned';

	constructor(public app: AppService) { }

	ngOnInit() {
		const unassignedCount = {};
		const assignedCount = {};

		this.unassignedList = [];
		this.assignedList = [];

		const all = Mangler();
		Mangler.each(this.app.entityManager.entities, (k, v) => all.add(v));

		all.items = EntitySortPipe.transform(all.items);

		all.each((i, entity: Entity) => {
			// Add device to assigned/unassigned list

			if (entity.isUnassigned()) {
				const type = entity.getTypeDescription();
				if (unassignedCount[type]) {
					unassignedCount[type] += 1;
				} else {
					unassignedCount[type] = 1;
					this.unassignedList.push({
						icon: entity.getIconClass(),
						description: type
					});
				}
			} else if (entity.hasTag('assignables-tree')) {
				const type = entity.getTypeDescription();
				if (assignedCount[type]) {
					assignedCount[type] += 1;
				} else {
					assignedCount[type] = 1;
					this.assignedList.push({
						icon: entity.getIconClass(),
						description: type
					});
				}
			}
		});

		this.unassignedList.forEach(item => {
			item['count'] = unassignedCount[item.description];
		});

		this.assignedList.forEach(item => {
			item['count'] = assignedCount[item.description];
		});
	}

}
