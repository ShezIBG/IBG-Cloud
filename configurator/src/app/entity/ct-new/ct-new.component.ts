import { CT } from './../ct';
import { Category } from './../category';
import { PM12 } from './../pm12';
import { ModalEvent } from './../../modal/modal.component';
import { ModalService } from './../../modal/modal.service';
import { Component } from '@angular/core';
import { EntityNewComponent } from '../../entity-decorators';

declare var Mangler: any;

@Component({
	selector: 'app-ct-new',
	templateUrl: './ct-new.component.html'
})
@EntityNewComponent(CT)
export class CTNewComponent {

	pm12: PM12;
	phase: string = null;
	pin = 0;
	data: any;
	categories: Category[] = [];

	editCategories = false;

	constructor(public modalService: ModalService) {
		this.pm12 = modalService.data.pm12;
		this.phase = this.modalService.data.phase;
		this.pin = this.modalService.data.pin;

		this.data = {
			entity: 'ct',
			id: this.pm12.entityManager.getAutoId(),
			pm12_id: this.pm12.data.id,
			pm12_pin_id: this.pin,
			pm12_pin: this.pm12.getPinDescription(this.pin, this.phase),
			location: this.pm12.getPinLocation(this.pin),
			long_description: '',
			short_description: 'CT-' + this.pm12.getPinShortDescription(this.pin, this.phase)
		};
	}

	modalHandler(event: ModalEvent) {
		if (event.type === 'button' && event.data.name === 'OK') {
			const newCTs: CT[] = [];

			if (this.phase === '3') {
				this.data.ct_group_id = this.data.id;
				const ct2 = Mangler.clone(this.data);
				const ct3 = Mangler.clone(this.data);

				this.data.long_description += ' ' + this.data.location;
				this.data.short_description += ' ' + this.data.location;

				ct2.id = this.pm12.entityManager.getAutoId();
				ct2.pm12_pin_id += 1
				ct2.location = this.pm12.getPinLocation(this.pin + 1);
				ct2.long_description += ' ' + ct2.location;
				ct2.short_description += ' ' + ct2.location;

				ct3.id = this.pm12.entityManager.getAutoId();
				ct3.pm12_pin_id += 2
				ct3.location = this.pm12.getPinLocation(this.pin + 2);
				ct3.long_description += ' ' + ct3.location;
				ct3.short_description += ' ' + ct3.location;

				newCTs.push(this.pm12.entityManager.createEntity(this.data) as CT);
				newCTs.push(this.pm12.entityManager.createEntity(ct2) as CT);
				newCTs.push(this.pm12.entityManager.createEntity(ct3) as CT);
			} else {
				newCTs.push(this.pm12.entityManager.createEntity(this.data) as CT);
			}

			newCTs.forEach((ct: CT) => {
				if (ct) ct.updateCategories(this.categories);
			});

			event.modal.close();
		} else {
			event.modal.close();
		}
	}

}
