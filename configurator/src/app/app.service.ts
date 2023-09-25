import { EntityTypes } from './entity/entity-types';
import { ApiService } from './api.service';
import { EntityChanges } from './entity/entity-changes';
import { ScreenService } from './screen/screen.service';
import { ModalLoaderComponent } from './modal/modal-loader.component';
import { EntityManager } from './entity/entity-manager';
import { Entity } from './entity/entity';
import { Building } from './entity/building';
import { Injectable, EventEmitter } from '@angular/core';

declare var Mangler: any;

export enum AppServiceState {
	Blank, Loading, Loaded, Error
}

@Injectable()
export class AppService {

	ampList = [1, 2, 3, 4, 5, 6, 8, 10, 15, 16, 20, 25, 30, 32, 40, 45, 50, 60, 63, 80, 100, 120, 200, 250, 300, 400, 600];

	state: AppServiceState = AppServiceState.Blank;
	error = '';
	toolbox: any[] = [];
	smoothPowerUnits: any[] = [];
	options: any = {};
	monitoring_bus_type: any = {};
	merged_results: any = {};
	entityManager: EntityManager = null;
	building: Building = null;
	modal: ModalLoaderComponent = null;
	selectedTab = 4;

	structureScreenService: ScreenService = null;
	equipmentScreenService: ScreenService = null;
	assignScreenService: ScreenService = null;
	floorplanScreenService: ScreenService = null;

	commitRunning = false;
	onCommitResult: EventEmitter<any> = new EventEmitter<any>();

	// If set, closing/reloading the window won't trigger the OnBeforeUnload event
	forcedReload = false;

	constructor(private api: ApiService) {
		// Keep session alive. Ping server every 5 minutes.
		setInterval(() => { this.api.general.ping(); }, 300000);
	}

	loadBuilding(id) {
		this.state = AppServiceState.Loading;

		this.api.configurator.getBuildingData(id, response => {
			try {
				const data = response.data || [];

				this.toolbox = data.toolbox;
				this.smoothPowerUnits = data.smoothpower_units || [];
				this.options = data.options || {};
				this.monitoring_bus_type = data.monitoring_bus_type || {};
				this.merged_results = data.merged_results || {};
				this.entityManager = new EntityManager(data.building);
				this.building = this.entityManager.getBuilding();

				if (this.building) {
					this.state = AppServiceState.Loaded;
				} else {
					this.state = AppServiceState.Error;
					this.error = 'Cannot load building.';
				}

				// Watch for entity removals
				this.entityManager.onEntityDeletedEvent.subscribe(entity => {
					if (EntityTypes.isSmoothPower(entity)) {
						const unit = Mangler.findOne(this.smoothPowerUnits, { id: entity.data.id });
						if (unit) {
							unit.building_id = null;
							unit.area_id = null;
							unit.router_id = null;
						}
					}
				});
			} catch (ex) {
				this.state = AppServiceState.Error;
				this.error = 'Error while processing building data. Check console for details. (' + ex.name + ': ' + ex.message + ')';
				console.log(ex);
			}
		}, response => {
			this.state = AppServiceState.Error;
			this.error = response.message;
		});
	}

	isLoaded() { return this.state === AppServiceState.Loaded; }
	isLoading() { return this.state === AppServiceState.Loading || this.state === AppServiceState.Blank; }
	hasError() { return this.state === AppServiceState.Error; }

	commitBuilding(changes: EntityChanges) {
		this.commitRunning = true;

		const data = {
			building_id: this.building.data.id,
			added: [],
			modified: [],
			deleted: []
		};

		Mangler.each(changes.addedEntities, (type, entities: Entity[]) => {
			Mangler.each(entities, (i, entity: Entity) => {
				data.added.push(entity.data);
			});
		});

		Mangler.each(changes.modifiedEntities, (type, modifications: any[]) => {
			Mangler.each(modifications, (i, modified: any) => {
				data.modified.push(Mangler.merge({ entity: modified.entity.type, id: modified.entity.data.id }, modified.changes));
			});
		});

		Mangler.each(changes.deletedEntities, (type, entities: Entity[]) => {
			Mangler.each(entities, (i, entity: Entity) => {
				data.deleted.push({ entity: entity.type, id: entity.data.id });
			});
		});

		this.api.configurator.commitChanges(data, response => {
			this.commitRunning = false;
			this.onCommitResult.emit(response);
		}, response => {
			this.commitRunning = false;
			this.onCommitResult.emit(response);
		});
	}

	uploadUserContent(fileElement, success, failure) {
		if (!fileElement) {
			failure('No file uploaded.');
			return;
		}

		const fileBrowser = fileElement.nativeElement;
		if (fileBrowser.files && fileBrowser.files[0]) {
			const formData = new FormData();
			formData.append('userfile', fileBrowser.files[0]);

			this.api.general.uploadUserContent(formData, res => {
				try {
					const file = res.data.files[0];
					const uc = this.building.entityManager.createEntity({
						entity: 'user_content',
						id: file.id,
						generated_url: file.url
					});
					success(uc);
				} catch (ex) {
					failure('No file uploaded.');
				}
			}, () => {
				failure('No file uploaded.');
			});
		} else {
			failure('No file uploaded.');
			return;
		}
	}

}
