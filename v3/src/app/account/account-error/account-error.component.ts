import { Component } from '@angular/core';

@Component({
	selector: 'app-account-error',
	template: `
		<h1 class="text-danger"><i class="md md-warning"></i> Error</h1>
		<p>There was an error while processing your account.</p>
	`
})
export class AccountErrorComponent { }
