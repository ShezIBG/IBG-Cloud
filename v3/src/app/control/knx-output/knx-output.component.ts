import { Component, Input, OnInit } from '@angular/core';
import { KnxValue } from '../knx-value';

@Component({
	selector: 'app-knx-output',
	templateUrl: './knx-output.component.html',
	styleUrls: ['./knx-output.component.less']
})
export class KnxOutputComponent implements OnInit {

	@Input() knxValue: KnxValue;

	constructor() { }

	ngOnInit() {
	}

}
