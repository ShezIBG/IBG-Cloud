import { FormsModule } from '@angular/forms';
import { SharedModule } from './../shared/shared.module';
import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { AccountRoutingModule } from './account-routing.module';
import { AccountDetailsComponent } from './account-details/account-details.component';
import { AccountErrorComponent } from './account-error/account-error.component';
import { AuthModule } from 'app/auth/auth.module';
import { CalendarModule } from 'primeng/primeng';

@NgModule({
	imports: [
		CommonModule,
		SharedModule,
		AccountRoutingModule,
		FormsModule,
		AuthModule,
		CalendarModule
	],
	declarations: [AccountDetailsComponent, AccountErrorComponent]
})
export class AccountModule { }
