import { ModuleComponent } from './../../shared/module/module.component';
import { Component } from '@angular/core';

@Component({
	selector: 'app-climate',
	template: `<router-outlet></router-outlet>`
})
export class ClimateComponent extends ModuleComponent { }
