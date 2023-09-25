export class Pagination {

	public maxTabs = 10;
	public pageSizeList = [20, 50, 100];

	private _count = 0;
	private _page = 1;
	private _pageCount = 1;
	private _pageSize = 50;

	get count() { return this._count; }
	get page() { return this._page; }
	get pageCount() { return this._pageCount; }
	get pageSize() { return this._pageSize; }

	set count(value) {
		this._count = Math.max(value, 0);
		this._pageCount = Math.max(1, Math.floor((this._count - 1) / this._pageSize) + 1);
		if (this._page > this._pageCount) this._page = this._pageCount;
	}

	set page(value) {
		this._page = Math.max(1, value);
		if (this._page > this._pageCount) this._page = this._pageCount;
	}

	set pageSize(value) {
		this._pageSize = Math.max(1, value);
		this._pageCount = Math.max(1, Math.floor((this._count - 1) / this._pageSize) + 1);
		if (this._page > this._pageCount) this._page = this._pageCount;
	}

	constructor(pageSize = 50) {
		this.pageSize = pageSize;
	}

	nextPage() { this.page += 1; }
	prevPage() { this.page -= 1; }

	startPosition() { return (this._page - 1) * this._pageSize; }

	getTabs() {
		let half = this.maxTabs;
		if (this.maxTabs % 2 === 0) half -= 1;
		half = Math.floor(half / 2);
		if (half < 2) half = 2;

		let min = this._page - half;
		let max = this._page + half;

		if (min < 1) {
			max += 1 - min;
			min = 1;
		}

		if (max > this._pageCount) {
			min -= max - this._pageCount;
			max = this._pageCount;
		}

		if (min < 1) min = 1;
		if (max > this._pageCount) max = this._pageCount;

		const result = [];
		for (let i = min; i <= max; i++) {
			result.push(i);
		}

		const length = result.length;
		if (length >= 5) {
			result[0] = 1;
			result[length - 1] = this._pageCount;
			if (result[1] !== result[0] + 1) result[1] = null;
			if (result[length - 1] !== result[length - 2] + 1) result[length - 2] = null;
		}

		return result;
	}

}
