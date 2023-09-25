import { ModalService } from './modal.service';
import { Component, OnInit, Input, EventEmitter, Output } from '@angular/core';

export class ModalEvent {
	constructor(
		public type: string,
		public data: any,
		public modal: ModalComponent
	) { }
}

@Component({
	selector: 'app-modal',
	templateUrl: './modal.component.html'
})
export class ModalComponent implements OnInit {

	@Input() modalTitle = 'Title';
	@Input() subtitle = '';
	@Input() buttons: any[] = [{ id: 0, name: 'Cancel', type: 'default' }, { id: 1, name: 'OK', type: 'primary' }];
	@Input() canClose = true;

	@Output() event = new EventEmitter<ModalEvent>();

	animate = false;

	constructor(public modalService: ModalService) {
	}

	ngOnInit() {
		// Process buttons passed as string (for easier setup)
		let i = 0;
		this.buttons = this.buttons.map(button => {
			if (typeof button === 'string') {
				const b = {
					id: i++,
					name: button,
					type: 'default'
				};

				const a = b.name.split('|');
				if (a.length > 1) {
					b.id = parseInt(a[0], 10);
					b.name = a[1];
				}

				if (b.name.startsWith('*')) {
					b.type = 'primary';
					b.name = b.name.substr(1);
				}

				if (b.name.startsWith('+')) {
					b.type = 'success';
					b.name = b.name.substr(1);
				}

				if (b.name.startsWith('!')) {
					b.type = 'danger';
					b.name = b.name.substr(1);
				}

				return b;
			}
			return button;
		});

		this.show();
	}

	show() {
		setTimeout(() => this.animate = true, 0);
	}

	close() {
		this.animate = false;
		setTimeout(() => {
			this.modalService.modalClosed.emit(new ModalEvent('close', null, this));
		}, 500);
	}

	buttonClicked(button) {
		this.event.emit(new ModalEvent('button', button, this));
	}

	closeClicked() {
		this.event.emit(new ModalEvent('close', null, this));
	}

	submitModal() {
		for (let i = 0; i < this.buttons.length; i++) {
			const button = this.buttons[i];
			if (button.type === 'primary') {
				this.buttonClicked(button);
				return;
			}
		}
	}

}
