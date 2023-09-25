import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { BrowserModule } from '@angular/platform-browser';
import { FormsModule } from '@angular/forms';
import { HttpClientModule } from '@angular/common/http';
import { NgModule, LOCALE_ID } from '@angular/core';

import { ApiService } from './api.service';
import { AppComponent } from './app.component';
import { AppRoutingModule } from './app-routing.module';
import { AppService } from './app.service';
import { ErrorComponent } from './error/error.component';
import { SharedModule } from './shared/shared.module';

// Import Angular locale data
import { registerLocaleData } from '@angular/common';
import localeENGB from '@angular/common/locales/en-GB';
registerLocaleData(localeENGB);

@NgModule({
	declarations: [
		AppComponent,
		ErrorComponent
	],
	imports: [
		SharedModule.forRoot(),
		BrowserModule,
		HttpClientModule,
		BrowserAnimationsModule,
		AppRoutingModule,
		FormsModule
	],
	providers: [
		AppService,
		ApiService,
		{ provide: LOCALE_ID, useValue: 'en-GB' }
	],
	bootstrap: [AppComponent]
})
export class AppModule { }
