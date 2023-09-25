import { CommitResultComponent } from './commit-result/commit-result.component';
import { ModalLoaderComponent } from './modal/modal-loader.component';
import { ModalService } from './modal/modal.service';
import { AppService } from './app.service';
import { Component, ViewChild, AfterViewInit, OnInit } from '@angular/core';

declare var Mangler: any;

@Component({
	selector: 'app-root',
	templateUrl: './app.component.html',
	styleUrls: ['./app.component.css'],
	providers: [AppService, ModalService],
	entryComponents: [CommitResultComponent]
})
export class AppComponent implements OnInit, AfterViewInit {

	@ViewChild(ModalLoaderComponent) modalLoader: ModalLoaderComponent;

	commitAdded = 0;
	commitModified = 0;
	commitDeleted = 0;

	constructor(public app: AppService) {
		app.onCommitResult.subscribe(result => {
			app.modal.open(CommitResultComponent, { result: result });
		});

		setInterval(() => {
			this.updateChanges();
		}, 10000);

		window.onbeforeunload = (e) => {
			// Don't do anything if the action was initiated from the configurator (after commit)
			if (this.app.forcedReload) return;

			this.updateChanges();

			if (this.commitAdded || this.commitDeleted || this.commitModified) {
				const result = 'You have uncommitted changes. Are you sure you want to leave the configurator without saving them?';
				e.returnValue = result;
				return result;
			}
		}
	};

	updateChanges() {
		this.commitAdded = 0;
		this.commitModified = 0;
		this.commitDeleted = 0;
		if (this.app.entityManager) {
			const changes = this.app.entityManager.getChanges();

			Mangler.each(changes.addedEntities, (k, v) => this.commitAdded += v.length);
			Mangler.each(changes.modifiedEntities, (k, v) => this.commitModified += v.length);
			Mangler.each(changes.deletedEntities, (k, v) => this.commitDeleted += v.length);
		}
	}

	getParameterByName(name, url = ''): string {
		if (!url) url = window.location.href;
		name = name.replace(/[\[\]]/g, '\\$&');
		const regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)');
		const results = regex.exec(url);
		if (!results) return '';
		if (!results[2]) return '';
		return decodeURIComponent(results[2].replace(/\+/g, ' '));
	}

	ngOnInit() {
		this.app.loadBuilding(this.getParameterByName('id'));
	}

	ngAfterViewInit() {
		this.app.modal = this.modalLoader;
	}

}
