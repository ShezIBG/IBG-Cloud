import { ModalEvent } from './../../modal/modal.component';
import { ModalService } from './../../modal/modal.service';
import { Entity, EntityCloneOption } from './../entity';
import { Component } from '@angular/core';

@Component({
	selector: 'app-entity-clone-modal',
	templateUrl: './entity-clone-modal.component.html'
})
export class EntityCloneModalComponent {

	entity: Entity;
	description: string;
	options: EntityCloneOption[];
	selectedOptions: any = {};

	numberOfCopies: any = 1;
	counterStart: any = 2;

	error = '';

	constructor(public modalService: ModalService) {
		this.entity = modalService.data.entity;
		this.description = this.entity.getCloneDescription();
		this.options = this.entity.getCloneOptions();

		// Check if description ends with a number.
		// If so, set counter and add placeholder.

		const words = this.description.split(' ') || [];
		const lastWord = words.length ? words[words.length - 1] : '';
		if (lastWord.match(/^\d+$/)) {
			const num = parseInt(lastWord, 10);
			if (!isNaN(num)) {
				words.pop();
				this.counterStart = num + 1;
			}
		}
		words.push('{counter}');
		this.description = words.join(' ');

		// Prepare selectedOptions array and fill with defaults
		this.options.forEach(option => {
			this.selectedOptions[option.key] = !!option.default;
		});
	}

	modalHandler(event: ModalEvent) {
		if (event.type === 'button' && event.data.name === 'OK') {
			this.error = '';

			// Make sure numbers are valid
			this.numberOfCopies = parseInt(this.numberOfCopies, 10) || 0;
			this.counterStart = parseInt(this.counterStart, 10) || 0;

			if (this.counterStart <= 0) this.error = 'Please enter a valid counter start value.';
			if (this.numberOfCopies <= 0) this.error = 'Please enter a valid number of copies.';
			if (this.error) return;

			Entity.canScroll = false;
			for (let counter = this.counterStart; counter < this.counterStart + this.numberOfCopies; counter++) {
				if (counter === this.counterStart + this.numberOfCopies - 1) Entity.canScroll = true;
				this.entity.clone((this.description || '').replace('{counter}', counter), this.selectedOptions);
			}
			Entity.canScroll = true;
		}
		event.modal.close();
	}

}
