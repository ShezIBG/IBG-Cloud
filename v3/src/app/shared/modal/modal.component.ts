import { ModalService } from './modal.service';
import { Component, OnInit, Input, EventEmitter, Output, OnChanges } from '@angular/core';

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
export class ModalComponent implements OnInit, OnChanges {

	@Input() modalTitle;
	@Input() subtitle;
	@Input() buttons: any[] = [{ id: 0, name: 'Cancel', type: 'default' }, { id: 1, name: 'OK', type: 'primary' }];
	@Input() canClose = true;
	@Input() size;
	@Input() icon;
	@Input() boxed = false;
	@Input() disableButtons = false;

	@Output() event = new EventEmitter<ModalEvent>();

	animate = false;

	constructor(public modalService: ModalService) { }

	ngOnChanges() {
		// Process buttons passed as string (for easier setup)
		let i = 0;
		this.buttons = this.buttons.map(button => {
			if (typeof button === 'string') {
				const b = {
					id: i++,
					name: button,
					type: 'default',
					pull: 'right'
				};

				const a = b.name.split('|');
				if (a.length > 1) {
					b.id = parseInt(a[0], 10);
					b.name = a[1];
				}

				if (b.name.startsWith('<')) {
					b.pull = 'left';
					b.name = b.name.substr(1);
				}

				if (b.name.startsWith('*')) {
					b.type = 'primary';
					b.name = b.name.substr(1);
				}

				if (b.name.startsWith('!')) {
					b.type = 'danger';
					b.name = b.name.substr(1);
				}

				if (b.name.startsWith('+')) {
					b.type = 'success';
					b.name = b.name.substr(1);
				}

				return b;
			}
			return button;
		});
	}

	ngOnInit() {
		this.show();
	}

	show() {
		setTimeout(() => this.animate = true, 0);
	}

	close(data = null) {
		this.animate = false;
		setTimeout(() => {
			this.modalService.modalClosed.emit(new ModalEvent('close', data, this));
		}, 150);
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
