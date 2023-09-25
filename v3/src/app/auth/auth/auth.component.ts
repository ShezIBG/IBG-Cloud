import { AppService } from './../../app.service';
import { Component } from '@angular/core';

@Component({
	selector: 'app-auth',
	template: `<div [ngClass]="{ 'theme-dark': app.branding === 'eticom', 'theme-light': app.branding === 'elanet' }"><router-outlet></router-outlet></div>`
})
export class AuthComponent {

	constructor(public app: AppService) { }

}
