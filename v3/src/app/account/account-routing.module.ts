import { AccountErrorComponent } from './account-error/account-error.component';
import { AccountDetailsComponent } from './account-details/account-details.component';
import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

const routes: Routes = [
	{ path: ':account/:token', component: AccountDetailsComponent },
	{ path: 'error', component: AccountErrorComponent }
];

@NgModule({
	imports: [RouterModule.forChild(routes)],
	exports: [RouterModule]
})
export class AccountRoutingModule { }
