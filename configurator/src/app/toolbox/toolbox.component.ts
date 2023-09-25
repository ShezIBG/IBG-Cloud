import { EntityTypes } from './../entity/entity-types';
import { EmLightType } from './../entity/em-light-type';
import { ScreenService } from './../screen/screen.service';
import { AppService } from './../app.service';
import { Area } from './../entity/area';
import { Entity } from './../entity/entity';
import { EntityManager } from './../entity/entity-manager';
import { Component } from '@angular/core';

declare var Mangler: any;

@Component({
	selector: 'app-toolbox',
	templateUrl: './toolbox.component.html',
	styleUrls: ['./toolbox.component.css']
})
export class ToolboxComponent {

	groups: any = [];
	selectedGroup = null;
	hovered = null;
	entityManager: EntityManager = new EntityManager([]);

	constructor(public app: AppService, public screen: ScreenService) {
		this.loadGroups();
	}

	loadGroups() {
		// Load toolbox from App service
		this.groups = Mangler.clone(this.app.toolbox);

		// Copy emergency light types to new entity manager
		// This makes sure emergency lights show the correct icons
		this.app.entityManager.find<EmLightType>(EntityTypes.EmLightType).forEach((emt: EmLightType) => {
			this.entityManager.createEntity(Mangler.clone(emt.data));
		});

		// Initialise group entities
		this.groups.forEach(group => {
			if (group.add && group.add.indexOf('em_light') !== -1) {
				// Add emergency light types
				this.app.entityManager.find<EmLightType>(EntityTypes.EmLightType).forEach((emt: EmLightType) => {
					group.items.push({
						entity: 'em_light',
						type_id: emt.data.id,
						description: emt.getDescription(),
						group_id: null,
						is_maintained: 0,
						zone_number: ''
					});
				});
			}

			group.items = group.items.map(item => {
				return this.entityManager.createEntity(item);
			});
		});

		// Filter entities
		this.groups.forEach(group => {
			group.items = group.items.filter(item => {
				return item.hasTag(this.screen.filter);
			});
		});

		// Delete empty groups
		this.groups = this.groups.filter(group => !!group.items.length);

		// Create a default "All" group
		const all = {
			id: 0,
			name: 'All',
			items: []
		};

		this.groups.forEach(group => {
			group.items.forEach(entity => {
				all.items.push(entity);
			});
		});

		this.groups.unshift(all);
		this.selectedGroup = all;
	}

	addEntity(entity: Entity) {
		const newComponent = entity.getNewComponent();
		if (newComponent) {
			// This entity has a popup form for new items, show
			this.app.modal.open(newComponent, {
				area: this.screen.treeEntity,
				entity: this.entityManager.createEntity(Mangler.clone(entity.data))
			});
		} else {
			// No new component, just add the item
			entity.copyToArea(this.screen.treeEntity as Area);
		}
	}

}
