import { Injectable } from '@angular/core';
import { environment } from './../environments/environment';
import { Http } from '@angular/http';

@Injectable()
export class ApiService {

	serverURL = '../api/v3';

	general = {
		ping: () => this.get('general/ping'),
		uploadUserContent: (formData, done, fail = null) => this.post('general/upload_user_content', formData, done, fail)
	};

	configurator = {
		getBuildingData: (buildingId, done, fail = null) => this.get('configurator/get_building_data?building_id=' + buildingId, done, fail),
		commitChanges: (data, done, fail = null) => this.post('configurator/commit_changes', data, done, fail)
	};

	constructor(private http: Http) {
		if (!environment.production) {
			this.serverURL = 'http://192.168.10.18/eticom/api/v3';
			console.log('DEV_MODE');
		}
	}

	private getJsonResponse(res, fail = null) {
		try {
			return res.json();
		} catch (ex) {
			if (fail) fail({
				status: 'FAIL',
				message: 'Internal server error.',
				data: null
			});
		}

		return null;
	}

	private get(action, done = null, fail = null) {
		this.http.get(this.serverURL + '/' + action).subscribe(res => {
			const json = this.getJsonResponse(res, fail);
			if (json && done) done(json);
		}, res => {
			const json = this.getJsonResponse(res, fail);
			if (json && fail) fail(json);
		});
	}

	private post(action, data = {}, done = null, fail = null) {
		this.http.post(this.serverURL + '/' + action, data).subscribe(res => {
			const json = this.getJsonResponse(res, fail);
			if (json && done) done(json);
		}, res => {
			const json = this.getJsonResponse(res, fail);
			if (json && fail) fail(json);
		});
	}

}
