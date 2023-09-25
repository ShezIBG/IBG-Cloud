import { SharedModule } from './../shared/shared.module';
import { FormsModule } from '@angular/forms';
import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { AuthRoutingModule } from './auth-routing.module';
import { LoginComponent } from './login/login.component';
import { LogoutComponent } from './logout/logout.component';
import { ResetComponent } from './reset/reset.component';
import { ResetSuccessfulComponent } from './reset-successful/reset-successful.component';
import { AuthHeaderComponent } from './auth-header/auth-header.component';
import { AuthFooterComponent } from './auth-footer/auth-footer.component';
import { AccessDeniedComponent } from './access-denied/access-denied.component';
import { PaymentOverdueComponent } from './payment-overdue/payment-overdue.component';
import { StartSubscriptionComponent } from './start-subscription/start-subscription.component';
import { AuthLogoSectionComponent } from './auth-logo-section/auth-logo-section.component';
import { ResetLinkComponent } from './reset-link/reset-link.component';
import { AuthComponent } from './auth/auth.component';
import { CustomerSignupComponent } from './customer-signup/customer-signup.component';

@NgModule({
	declarations: [
		LoginComponent,
		LogoutComponent,
		ResetComponent,
		ResetSuccessfulComponent,
		AuthHeaderComponent,
		AuthFooterComponent,
		AccessDeniedComponent,
		PaymentOverdueComponent,
		StartSubscriptionComponent,
		AuthLogoSectionComponent,
		ResetLinkComponent,
		AuthComponent,
		CustomerSignupComponent
	],
	imports: [
		CommonModule,
		FormsModule,
		SharedModule,
		AuthRoutingModule
	],
	exports: [
		AuthHeaderComponent,
		AuthFooterComponent
	]
})
export class AuthModule { }
