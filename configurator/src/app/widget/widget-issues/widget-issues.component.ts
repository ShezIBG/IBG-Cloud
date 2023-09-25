import { FiberHeadendServer } from './../../entity/fiber-headend-server';
import { EntityTypes } from './../../entity/entity-types';
import { EntityManager } from './../../entity/entity-manager';
import { Router } from './../../entity/router';
import { AppService } from './../../app.service';
import { Entity, isActivatableEntity } from './../../entity/entity';
import { EntitySortPipe } from './../../entity/entity-sort.pipe';
import { Component, OnInit } from '@angular/core';

declare var Mangler: any;

@Component({
	selector: 'widget-issues',
	templateUrl: './widget-issues.component.html',
	styleUrls: ['./widget-issues.component.css']
})
export class WidgetIssuesComponent implements OnInit {

	issues = [];

	constructor(public app: AppService) { }

	ngOnInit() {
		const all = Mangler();
		Mangler.each(this.app.entityManager.entities, (k, v) => all.add(v));

		all.items = EntitySortPipe.transform(all.items);

		const router = this.app.entityManager.findOne<Router>(EntityTypes.Router);
		if (!router) {
			this.issues.push({
				entity: new Router({ description: 'Router' }, new EntityManager([])),
				issue: 'No routers found'
			});
		}

		all.each((i, entity: Entity) => {
			// Check if collector has serial number set and is connected to a router
			if (EntityTypes.isGateway(entity)) {
				if (('' + entity.data.pi_serial).length !== 16) {
					this.issues.push({
						entity: entity,
						issue: 'Invalid serial'
					});
				}

				if (entity.isUnassigned()) {
					this.issues.push({
						entity: entity,
						issue: 'Not connected'
					});
				}
			}

			if (EntityTypes.isDistBoard(entity)) {
				// Check if DBs have breakers
				if (!entity.data.is_virtual && !entity.hasBreakers()) {
					this.issues.push({
						entity: entity,
						issue: 'No breakers'
					});
				}

				// Check if DBs have feed breakers set
				if (!entity.data.is_virtual && !entity.data.feed_breaker_id) {
					this.issues.push({
						entity: entity,
						issue: 'No feed breaker set'
					});
				}
			}

			// Check if real electricity meters have MPAN set
			if (EntityTypes.isMeter(entity)) {
				if (entity.data.meter_type === 'E' && entity.is_supply_meter && !entity.data.mpan) {
					this.issues.push({
						entity: entity,
						issue: 'No MPAN set'
					});
				}
			}

			// Check if PM12 has CTs
			if (EntityTypes.isPM12(entity)) {
				if (!entity.hasCTs()) {
					this.issues.push({
						entity: entity,
						issue: 'No CTs'
					});
				}
			}

			// Check if ABBs are monitored
			if (EntityTypes.isABBMeter(entity)) {
				if (!entity.getBusID(entity.data.bus_type)) {
					this.issues.push({
						entity: entity,
						issue: 'Not monitored'
					});
				}
			}

			// Check if SmoothPower units are connected
			if (EntityTypes.isSmoothPower(entity)) {
				if (entity.isUnassigned()) {
					this.issues.push({
						entity: entity,
						issue: 'Not connected'
					});
				}
			}

			// Check if headend servers / OLTs / ONUs are connected
			if (EntityTypes.isFiberHeadendServer(entity) || EntityTypes.isFiberOLT(entity) || EntityTypes.isFiberONU(entity)) {
				if (entity.isUnassigned()) {
					this.issues.push({
						entity: entity,
						issue: 'Not connected'
					});
				}
			}

			// If there is a headend server connected, router MUST have external ip set
			if (EntityTypes.isRouter(entity)) {
				const hes = this.app.entityManager.findOne<FiberHeadendServer>(EntityTypes.FiberHeadendServer, { router_id: entity.data.id });
				if (hes && !entity.data.ip_address) {
					this.issues.push({
						entity: entity,
						issue: 'No external IP set'
					});
				}
			}

			// Check if aircon objects are connected
			if (EntityTypes.isBuildingServer(entity) || EntityTypes.isCoolHub(entity) || EntityTypes.isCoolPlug(entity)) {
				if (entity.isUnassigned()) {
					this.issues.push({
						entity: entity,
						issue: 'Not connected'
					});
				}
			}

			// Check if DALI lights are connected
			if (EntityTypes.isDaliLight(entity)) {
				if (entity.isUnassigned()) {
					this.issues.push({
						entity: entity,
						issue: 'Not connected'
					});
				}
			}

			// Check if entity is activatable and alert if it's disabled
			if (isActivatableEntity(entity) && !entity.isActive) {
				this.issues.push({
					entity: entity,
					issue: 'Inactive'
				});
			}

			// Check for unassigned relay pins and states
			if (EntityTypes.isRelayPin(entity) && entity.isUnassigned()) {
				this.issues.push({
					entity: entity,
					issue: 'Unassigned pin'
				});
			}
			if (EntityTypes.isRelayEndDevice(entity) && entity.data.state_pin_id === null) {
				this.issues.push({
					entity: entity,
					issue: 'State pin not set'
				});
			}

		});

	}

}
