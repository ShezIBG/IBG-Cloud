import { EticomCloudPage } from './app.po';

describe('eticom-cloud App', () => {
	let page: EticomCloudPage;

	beforeEach(() => {
		page = new EticomCloudPage();
	});

	it('should display welcome message', () => {
		page.navigateTo();
		expect(page.getParagraphText()).toEqual('Welcome to app!!');
	});
});
