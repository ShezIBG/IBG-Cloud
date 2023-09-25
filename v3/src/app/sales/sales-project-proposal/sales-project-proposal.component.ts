import { HeaderTab } from './../../shared/header/header.component';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from './../../api.service';
import { AppService } from './../../app.service';
import { Location } from '@angular/common';
import { Component, OnInit, OnDestroy } from '@angular/core';

declare var Mangler: any;

@Component({
	selector: 'app-sales-project-proposal',
	templateUrl: './sales-project-proposal.component.html'
})
export class SalesProjectProposalComponent implements OnInit, OnDestroy {

	get show_quantities() { return !!this.data.proposal.show_quantities; }
	set show_quantities(value) { this.data.proposal.show_quantities = value ? 1 : 0; }

	get show_subtotals() { return !!this.data.proposal.show_subtotals; }
	set show_subtotals(value) { this.data.proposal.show_subtotals = value ? 1 : 0; }

	get show_acceptance() { return !!this.data.proposal.show_acceptance; }
	set show_acceptance(value) { this.data.proposal.show_acceptance = value ? 1 : 0; }

	id;
	data;
	dirty = false; // Any unsaved changes?
	saved = false; // Has it been saved since the form was loaded?
	error = false; // Any errors thrown when saving?
	activeTab = 'settings';
	saveTimer;
	destroyed = false;

	settingsTab: HeaderTab = { id: 'settings', title: 'Settings' };
	introductionTab: HeaderTab = { id: 'introduction', title: 'Introduction' }
	modulesTab: HeaderTab = { id: 'modules', title: 'Modules', hidden: true };
	summaryTab: HeaderTab = { id: 'summary', title: 'Proposal Summary' };
	quotationTab: HeaderTab = { id: 'quotation', title: 'Quotation' };
	extrasTab: HeaderTab = { id: 'extras', title: 'Extras' };
	tabs: HeaderTab[] = [
		this.settingsTab,
		this.introductionTab,
		this.modulesTab,
		this.summaryTab,
		this.quotationTab,
		this.extrasTab
	];

	private sub: any;

	constructor(
		private app: AppService,
		private api: ApiService,
		private route: ActivatedRoute,
		private location: Location
	) { }

	ngOnInit() {
		this.sub = this.route.params.subscribe(params => {
			this.id = params['projectId'] || 'new';
			this.loadProposal();
		});
	}

	ngOnDestroy() {
		clearTimeout(this.saveTimer);
		this.destroyed = true;
		this.sub.unsubscribe();
	}

	loadProposal() {
		this.api.sales.getProjectProposal(this.id, response => {
			setTimeout(() => {
				this.app.header.setTab('proposal');
				this.app.header.clearButtons();
			}, 0);

			this.data = response.data || {};
			this.dirty = false;
			this.updateTabs();
		}, response => {
			this.app.notifications.showDanger(response.message);
		});
	}

	printFullProposal() { window.open(this.data.print_url, '_blank'); }
	printSimpleQuotation(hideLabour = false) { window.open(this.data.print_simple_url + '&hide_labour=' + (hideLabour ? 1 : 0), '_blank'); }
	printItemisedQuotation(hideLabour = false) { window.open(this.data.print_itemised_url + '&hide_labour=' + (hideLabour ? 1 : 0), '_blank'); }
	printAreaSummary(hideLabour = false) { window.open(this.data.print_area_url + '&hide_labour=' + (hideLabour ? 1 : 0), '_blank'); }

	downloadFullProposal() { window.open(this.data.download_url, '_blank'); }
	downloadSimpleQuotation(hideLabour = false) { window.open(this.data.download_simple_url + '&hide_labour=' + (hideLabour ? 1 : 0), '_blank'); }
	downloadItemisedQuotation(hideLabour = false) { window.open(this.data.download_itemised_url + '&hide_labour=' + (hideLabour ? 1 : 0), '_blank'); }
	downloadAreaSummary(hideLabour = false) { window.open(this.data.download_area_url + '&hide_labour=' + (hideLabour ? 1 : 0), '_blank'); }

	goBack() {
		this.location.back();
	}

	scheduleSave() {
		clearTimeout(this.saveTimer);
		if (this.destroyed) return;

		this.saveTimer = setTimeout(() => {
			this.save();
		}, 1000);
	}

	save() {
		clearTimeout(this.saveTimer);
		if (this.destroyed) return;
		this.error = false;

		this.api.sales.saveProjectProposal(this.data, () => {
			if (this.destroyed) return;
			this.saved = true;
			this.dirty = false;
		}, response => {
			if (this.destroyed) return;
			this.error = true;
			this.app.notifications.showDanger(response.message);
		});
	}

	badgeCount(items: any[]) {
		let count = 0;
		items.forEach(item => { if (item) count++; });
		return count ? '' + count : '';
	}

	updateTabs() {
		if (!this.data) {
			this.modulesTab.hidden = true;
			this.modulesTab.badge = '';
			this.introductionTab.badge = '';
			this.summaryTab.badge = '';
			this.quotationTab.badge = '';
			this.extrasTab.badge = '';
		} else {
			this.modulesTab.hidden = !this.data.modules.length;
			this.modulesTab.badge = this.badgeCount(this.data.modules.map(item => !!item.text_features));
			this.introductionTab.badge = this.badgeCount([!!this.data.proposal.text_introduction, !!this.data.proposal.text_solution]);
			this.summaryTab.badge = this.badgeCount([!!this.data.proposal.text_summary, !!this.data.proposal.text_payback]);
			this.quotationTab.badge = this.badgeCount([!!this.data.proposal.text_quotation, !!this.data.proposal.text_subscriptions]);
			this.extrasTab.badge = this.badgeCount([!!this.data.proposal.text_payment, !!this.data.proposal.text_terms]);
		}
	}

	setTab(id) {
		if (id === null || typeof id === 'undefined') return;
		if (Mangler.findOne(this.tabs, { id })) {
			this.activeTab = id;
		}
	}

	setDirty() {
		this.dirty = true;
		this.updateTabs();
		this.scheduleSave();
	}

}
