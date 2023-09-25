import { ModuleComponent } from './../../shared/module/module.component';
import { Component } from '@angular/core';

@Component({
	selector: 'app-control',
	template: `<router-outlet></router-outlet>`
})
export class ControlComponent extends ModuleComponent { }
