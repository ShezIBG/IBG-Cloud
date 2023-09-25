import { ScreenService } from './../../screen/screen.service';
import { Area } from './../area';
import { Entity } from './../entity';
import { Component, OnInit, OnDestroy } from '@angular/core';
import { EntityTreeComponent } from '../../entity-decorators';

@Component({
	selector: 'app-area-items',
	templateUrl: './area-items.component.html'
})
@EntityTreeComponent(Area)
export class AreaItemsComponent implements OnInit, OnDestroy {

	entity: Area;

	hovered = null;
	subscriptions = [];

	constructor(public screen: ScreenService) {
		this.entity = screen.treeEntity as Area;
	}

	ngOnInit() {
		this.subscriptions.push(this.entity.entityManager.onEntityAddedEvent.subscribe((entity: Entity) => {
			if (this.entity.items.indexOf(entity) !== -1 && entity.hasTag(this.screen.filter)) this.screen.selectDetailEntity(entity);
		}));

		this.subscriptions.push(this.entity.entityManager.onEntityDeletedEvent.subscribe((entity: Entity) => {
			if (this.screen.detailEntity === entity && entity.hasTag(this.screen.filter)) this.screen.selectDetailEntity(null);
		}));

		this.subscriptions.push(this.screen.detailEntitySelected.subscribe((entity: Entity) => {
			if (entity) entity.scrollIntoView();
		}));

		this.screen.selectDetailEntity(this.entity);
	}

	ngOnDestroy() {
		this.subscriptions.forEach(sub => sub.unsubscribe());
	}

}
