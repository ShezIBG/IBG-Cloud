import { EntityTypes } from './../entity-types';
import { Category } from './../category';
import { ModalEvent } from './../../modal/modal.component';
import { CT } from './../ct';
import { ModalService } from './../../modal/modal.service';
import { PM12 } from './../pm12';
import { Component } from '@angular/core';
import { EntityDetailComponent } from '../../entity-decorators';

@Component({
	selector: 'app-ct-edit',
	templateUrl: './ct-edit.component.html'
})
@EntityDetailComponent(CT)
export class CTEditComponent {

	ct: CT;
	data: any = {};
	categories: Category[] = [];
	pm12: PM12 = null;

	editCategories = false;

	constructor(public modalService: ModalService) {
		this.ct = modalService.data.entity;
		this.categories = this.ct.getCategories();

		const parent = this.ct.getParent();
		this.pm12 = EntityTypes.isPM12(parent) ? parent : null;

		this.data.long_description = this.ct.data.long_description;
		this.data.short_description = this.ct.data.short_description;

		if (this.ct.is3P()) {
			this.data.long_description = this.data.long_description.replace(/\sL1$|\sL2$|\sL3$/, '');
			this.data.short_description = this.data.short_description.replace(/\sL1$|\sL2$|\sL3$/, '');
		}
	}

	modalHandler(event: ModalEvent) {
		if (event.type === 'button' && event.data.name === 'OK') {
			if (this.ct.is3P()) {
				this.ct.getAllCTSFromGroup().forEach((ct: CT) => {
					ct.data.long_description = this.data.long_description + ' ' + ct.data.location;
					ct.data.short_description = this.data.short_description + ' ' + ct.data.location;
					ct.updateCategories(this.categories);
				});
			} else {
				this.ct.data.long_description = this.data.long_description;
				this.ct.data.short_description = this.data.short_description;
				this.ct.updateCategories(this.categories);
			}
			event.modal.close();
		} else {
			event.modal.close();
		}
	}

}
