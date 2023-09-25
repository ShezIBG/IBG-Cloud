import { ModuleComponent } from './../../shared/module/module.component';
import { Component } from '@angular/core';

@Component({
	selector: 'app-relay',
	template: `<router-outlet></router-outlet>`
})
export class RelayComponent extends ModuleComponent { }
