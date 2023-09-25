import { AuthComponent } from './auth/auth.component';
import { ResetLinkComponent } from './reset-link/reset-link.component';
import { StartSubscriptionComponent } from './start-subscription/start-subscription.component';
import { PaymentOverdueComponent } from './payment-overdue/payment-overdue.component';
import { AccessDeniedComponent } from './access-denied/access-denied.component';
import { ResetSuccessfulComponent } from './reset-successful/reset-successful.component';
import { ResetComponent } from './reset/reset.component';
import { LogoutComponent } from './logout/logout.component';
import { LoginComponent } from './login/login.component';
import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { CustomerSignupComponent } from './customer-signup/customer-signup.component';

const routes: Routes = [
	{
		path: '', component: AuthComponent, children: [
			{ path: '', pathMatch: 'full', redirectTo: 'login' },
			{ path: 'login', component: LoginComponent, data: { auth: false } },
			{ path: 'logout', component: LogoutComponent, data: { auth: false } },
			{ path: 'reset/:token', component: ResetLinkComponent, data: { auth: false } },
			{ path: 'reset', component: ResetComponent, data: { auth: false } },
			{ path: 'reset-successful', component: ResetSuccessfulComponent, data: { auth: false } },
			{ path: 'access-denied', component: AccessDeniedComponent, data: { auth: false } },
			{ path: 'customer-signup/:id/:hash', component: CustomerSignupComponent, data: { auth: false } },
			{ path: 'payment-overdue', component: PaymentOverdueComponent },
			{ path: 'start-subscription', component: StartSubscriptionComponent }
		]
	}
];

@NgModule({
	imports: [RouterModule.forChild(routes)],
	exports: [RouterModule]
})
export class AuthRoutingModule { }
