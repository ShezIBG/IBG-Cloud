import { ModalService } from './../modal/modal.service';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { ModalComponent } from './../modal/modal.component';
import { Component, OnInit, ViewChild } from '@angular/core';

declare var Mangler: any;

@Component({
	selector: 'app-ui-element-modal',
	templateUrl: './ui-element-modal.component.html',
	styleUrls: ['./ui-element-modal.component.less']
})
export class UIElementModalComponent implements OnInit {

	@ViewChild(ModalComponent) modal: ModalComponent;

	buttons = [];

	data;

	private _selected;
	get selected() { return this._selected; }
	set selected(value) {
		this._selected = value;
		this.refreshSelected();
	}

	elementName;
	presetId;
	description;
	columns;

	constructor(
		private app: AppService,
		private api: ApiService,
		private modalService: ModalService
	) { }

	ngOnInit() {
		this.elementName = this.modalService.data;
		this.reload();
	}

	reload() {
		this.selected = null;
		this.api.general.getUIElementData(this.elementName, response => {
			this.data = response.data;

			this.data.presets.forEach(preset => {
				preset.is_selected = !!preset.is_selected;
				preset.columns.forEach(column => {
					column.is_visible = !!column.is_visible;
				});

				if (preset.is_selected) this.selected = preset;
			});

			if (!this.selected) this.selected = this.data.presets[0];

			this.data.presets.push({
				id: null,
				description: 'New Preset',
				user_id: 1
			});

		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	refreshSelected() {
		if (!this.selected) {
			this.presetId = null;
			this.description = '';
			this.columns = [];
			return;
		}

		this.presetId = this.selected.id;
		this.description = this.selected.description;
		if (this.presetId) {
			this.columns = Mangler.clone(this.selected.columns);

			if (this.selected.user_id) {
				// Add missing columns from default template
				const defaultColumns = Mangler.clone(this.data.presets[0].columns);
				defaultColumns.forEach(column => {
					if (!Mangler.findOne(this.columns, { id: column.id })) {
						column.is_visible = false;
						this.columns.push(column);
					}
				});
			}
		}

		if (this.presetId && this.selected.user_id) {
			this.buttons = ['2|<!Delete Preset', '0|Cancel', '1|*OK']
		} else {
			this.buttons = ['0|Cancel', '1|*OK']
		}
	}

	modalHandler(event) {
		if (event.data) {
			switch (event.data.id) {
				case 1:
					this.api.general.saveUIElementData({
						element_name: this.elementName,
						preset_id: this.presetId,
						description: this.description,
						columns: this.columns
					}, () => {
						this.modal.close();
					}, response => {
						this.app.notifications.showDanger(response.message);
					});
					this.modal.close();
					break;

				case 2:
					this.api.general.deleteUIElementData(this.presetId, () => {
						this.app.notifications.showSuccess('Preset deleted.');
						this.reload();
					}, response => {
						this.app.notifications.showDanger(response.message);
					});
					break;

				default:
					this.modal.close();
					break;
			}
		} else {
			this.modal.close();
		}
	}

	toolboxDrop(event) {
		// Update data model
		const previousIndex = event.previousIndex;
		const currentIndex = event.currentIndex;

		if (previousIndex === currentIndex) return; // No change

		const item = this.columns.splice(previousIndex, 1)[0];
		this.columns.splice(currentIndex, 0, item);
	}

}
