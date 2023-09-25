import { Component } from '@angular/core';

@Component({
	selector: 'app-error',
	template: `
		<div class="p-40">
			<h1 class="text-danger">404 <span class="subtitle">Page not found</span></h1>
			<p>
				Use the back button of your browser, or <a routerLink="/">click here</a>.
			</p>
		</div>
	`
})
export class ErrorComponent { }
