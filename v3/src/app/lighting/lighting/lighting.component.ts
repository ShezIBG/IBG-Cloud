import { ModuleComponent } from './../../shared/module/module.component';
import { Component } from '@angular/core';

@Component({
	selector: 'app-lighting',
	template: `<router-outlet></router-outlet>`
})
export class LightingComponent extends ModuleComponent { }
