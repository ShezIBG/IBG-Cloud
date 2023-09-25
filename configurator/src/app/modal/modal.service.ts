import { ModalEvent } from './modal.component';
import { Injectable, EventEmitter } from '@angular/core';

@Injectable()
export class ModalService {

	modalClosed = new EventEmitter<ModalEvent>();
	data: any = null;

}
