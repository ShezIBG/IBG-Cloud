import { ModuleComponent } from './../../shared/module/module.component';
import { Component } from '@angular/core';

@Component({
	selector: 'app-emergency',
	template: `<router-outlet></router-outlet>`
})
export class EmergencyComponent extends ModuleComponent { }
