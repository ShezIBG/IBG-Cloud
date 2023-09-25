import { Component, Input, OnInit, Output } from '@angular/core';
import { EventEmitter } from '@angular/core';
import { KnxValue } from '../knx-value';

@Component({
	selector: 'app-knx-input',
	templateUrl: './knx-input.component.html',
	styleUrls: ['./knx-input.component.less']
})
export class KnxInputComponent implements OnInit {

	@Input() knxValue: KnxValue;
	@Output() change = new EventEmitter<KnxValue>();

	constructor() { }

	ngOnInit() {
	}

	typedValueChanged() {
		this.knxValue.fromTyped();
		this.change.emit(this.knxValue);
	}

}
