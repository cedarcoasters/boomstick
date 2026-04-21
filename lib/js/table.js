import {Loop} from '/js/loop.js';


/**
 * Base Table Class that builds generic tables
 *
 * @param {string} name - The name of the Table
 * @param {string} tagTemplate - The css selector used to locate the table template
 * @param {string} tagTarget - The css selector used to locate the target for the table
 * @param {boolean} skipLoading - If set to false (_default_) the table will be automatically loaded when the object is created
 */
export class Table {

	constructor(name, tagTemplate, tagTarget, skipLoading=false) {
		this.name         = name;
		this.tagContainer = tagTemplate;
		this.selTable     = 'div#table-'+this.name+'>table';
		this.selHead      = this.selTable+'>thead';
		this.selBody      = this.selTable+'>tbody';
		this.selFoot      = this.selTable+'>tfoot';



		this.objContainer = $(this.tagContainer).clone();

		this.objTableH      = this.objContainer.children('table#horizontal').clone();
		this.objTableV      = this.objContainer.children('table#vertical').clone();
		// Default Header
		this.objHead       = this.objTableH.children('thead#default').clone();
		this.objHeadTRHead = this.objHead.children('tr.header').clone();
		this.objHeadTH     = this.objHeadTRHead.children('th').clone();
		// Frozen Header
		this.objHeadFr       = this.objTableH.children('thead#freeze-header').clone();
		this.objHeadFrTRHead = this.objHeadFr.children('tr.header').clone();
		this.objHeadFrTH     = this.objHeadFrTRHead.children('th').clone();
		this.objHeadFrTRData = this.objHeadFr.children('tr.data').clone();
		this.objHeadFrTD     = this.objHeadFrTRData.children('td').clone();
		// Body Horizontal (default)
		this.objBodyH       = this.objTableH.children('tbody').clone();
		this.objBodyTRH     = this.objBodyH.children('tr').clone();
		this.objBodyTDH     = this.objBodyTRH.children('td').clone();
		// Body Vertical
		this.objBodyV        = this.objTableV.children('tbody').clone();
		this.objBodyTRV      = this.objBodyV.children('tr').clone();
		this.objBodyTDVLabel = this.objBodyTRV.children('td#label').clone().attr('id', null);
		this.objBodyTDVValue = this.objBodyTRV.children('td#value').clone().attr('id', null);
		// Footer
		this.objFoot       = this.objTableH.children('tfoot').clone();

		this.objLoading      = this.objContainer.children('div.loading-records').clone();
		this.objNoRecords    = this.objContainer.children('div.no-records').clone();
		this.objNoDataSource = this.objContainer.children('div.no-data-source').clone();
		this.objErrorMessage = this.objContainer.children('div.error-message').clone();

		this.objSortArrowHorz = this.objContainer.children('div.sort-arrow-horz').clone();
		this.objSortArrowVert = this.objContainer.children('div.sort-arrow-vert').clone();

		this.tagTarget    = tagTarget;
		this.objTarget    = $(this.tagTarget);
		this.rowIdField   = null;

		this.isSortable     = false;
		this.sortFieldsOmit = [];
		this.sortOrder      = [];
		this.sortFields     = [];

		this.freezeHeader = {
			 active      : false
			,tableWidth  : '100%'
			,tableHeight : '400px'
			,atRow       : 0
			,postFreeze  : null
		}

		this.tableType = 'horizontal'; // Default to horizontal rows

		this.tableTypeVerticalMaxRows = 50; // Beyond this, it makes no sense to have a horizontal scroll and vertical rows

		this.columnOrder    = null;

		this.filterParams    = null;
		this.sourceMethod    = 'GET';
		this.sourceHeaders   = {
			'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
			'Accept':       'application/json'
		};
		this.sourceURL       = null;
		this.sourceData      = null;
		this.sourceOptions   = null;
		this.sourceIsLoading = false;
		this.sourceReload    = true;
		this._abortController = null;

		this.reloadLoop      = null;
		this.reloadFreqms    = null;
		this.lastReloadTime  = null;

		this.preFetchFunc        = null;
		this.postFetchFunc       = null;
		this.preLoadFunc         = null;
		this.postLoadFunc        = null;
		this.firstLoadFunc       = null;
		this.firstLoadFuncCalled = false;

		this.hideFields = [];

		// Callback definitions
		this.onRenderCell = {};

		if(skipLoading === true){
			var newNoRecords = this.objNoRecords.clone().removeClass('d-none');
			this.objTarget.html(newNoRecords).removeClass('d-none');
		}
		else {
			this.showLoading();
		}
	}


	clear()
	{
		let newNoRecords = this.objNoRecords.clone().removeClass('d-none');
		this.objTarget.html(newNoRecords).removeClass('d-none');
		this.sourceData    = null;
		this.sourceOptions = null;
	}


	showLoading()
	{
		const newLoading = this.objLoading.clone().removeClass('d-none');
		this.objTarget.html(newLoading);
	}


	showError(msg = null)
	{
		const newErrorMessage = this.objErrorMessage.clone().removeClass('d-none');
		newErrorMessage.children('i').text(
			msg === null ? '-- Data source is invalid --' : msg
		);
		this.objTarget.html(newErrorMessage);
	}


	initSource()
	{
		const requiredKeys = [
			 'labels_by_field'
			,'fields_by_label'
			,'table_data'
			,'row_count'
			,'row_total'
		];

		let hasValidSourceDataKeys   = requiredKeys.every(key => key in this.sourceData);
		let hasInvalidSourceDataKeys = (hasValidSourceDataKeys) ? false : true;

		if(hasInvalidSourceDataKeys) {
			this.showError()
			return false;
		}

		let hasRecords = (this.sourceData['row_count'] > 0);
		let hasNoRecords = (hasRecords) ? false : true;

		if(hasNoRecords) {
			let newNoRecords = this.objNoRecords.clone().removeClass('d-none');
			this.objTarget.html(newNoRecords).removeClass('d-none');
			return false;
		}

		if(this.columnOrder) {
			let missing = [];
			for(const [field, label] of Object.entries(this.sourceData['labels_by_field'])) {
				if(field in this.columnOrder) {
					continue;
				}
				this.columnOrder.push(field);
			}
			let ro = {
				 'labels_by_field':{}
				,'fields_by_label':{}
			};
			for(let key in this.columnOrder) {
				let col = this.columnOrder[key];
				ro['labels_by_field'][col] = this.sourceData['labels_by_field'][col];
				ro['fields_by_label'][ro['labels_by_field'][col]] = col;
			}
			this.sourceData['labels_by_field'] = ro['labels_by_field'];
			this.sourceData['fields_by_label'] = ro['fields_by_label'];
		}

		if(this.freezeHeader.active === true) {
			this.sourceData['table_header_data'] = this.sourceData['table_data'].splice(0, this.freezeHeader.atRow);
		}

		if(this.sortFields) {
			this._sort();
		}

		return true;
	}

	async fetchSourceData()
	{
		// Abort any previous in-flight request
		if (this._abortController) {
			this._abortController.abort();
		}
		this._abortController = new AbortController();

		const self   = this;
		const signal = this._abortController.signal;

		return new Promise((resolve, reject) => {
			let contentType = {'X-Requested-With': 'XMLHttpRequest'}
			if(self.sourceMethod === 'POST') {
				contentType['Content-Type'] = 'application/x-www-form-urlencoded';
			}

			fetch(
				 self.sourceURL
				,{
					 method  : self.sourceMethod
					,headers : {...self.sourceHeaders, ...contentType}
					,body    : self.filterParams
					,signal  : signal
				}
			)
				.then(response => {
					if(response.ok === true) {
						return response.json();
					}
					else {
						throw new Error('Network error, data was not retrieved.');
					}
				})
				.then(response => {
					if (!('data' in response)) {
						throw new Error('Invalid response: missing required "data" key that holds the table data.');
					}
					this.sourceData    = response['data'];

					if ('options' in response) {
						this.sourceOptions = response['options'];
					}
					resolve(response);
				}).catch(error => {
					reject(error);
				});
		})
	}

	async load({withIndicator = false} = {})
	{
		const wi = withIndicator ?? false;
		if(this.reloadFreqms > 0 && this.reloadLoop == null) {
			const self = this;
			this.reloadLoop = new Loop('load()::reloadLoop', function(){
				self.sourceReload = true;
				// automated reloading should not show the loading indicator, despite what was passed as an arg.
				self.load({withIndicator: false});
			}, this.reloadFreqms);
		}
		else {
			const self = this;
			return new Promise((resolve, reject) => {
				$('*').addClass('cursor-progress');
				if(wi) {
					self.showLoading();
				}
				self._callPreFetchFunc();
				self.fetchSourceData().then(() => {
					self._callPostFetchFunc();
					resolve(self._load(self.initSource()));
				}).catch(error => {
					if (error.name === 'AbortError') {
						resolve(); // Silently ignore cancelled requests
						return;
					}
					const msg = 'There was an error processing the source data: '+error.message;
					self.showError(msg)
					throw new Error(msg);
				}).finally(() => {
					$('*').removeClass('cursor-progress');
				});
			});
		}
	}

	async reload()
	{
		this.columnOrder  = null;
		this.sourceReload = true;
		return this.load()
	}


	_load(initResponse)
	{
		this._callPreLoadFunc();

		if(initResponse == false) {
			this.lastReloadTime = Date.now();
			this._callFirstLoadFunc();
			this._callPostLoadFunc();
			return;
		}

		this.build(this.sourceData);
	}

	_callPreFetchFunc()
	{
		if(typeof this.preFetchFunc == 'function') {
			this.preFetchFunc();
		}
	}
	_callPostFetchFunc()
	{
		if(typeof this.postFetchFunc == 'function') {
			this.postFetchFunc();
		}
	}
	_callPreLoadFunc()
	{
		if(typeof this.preLoadFunc == 'function') {
			this.preLoadFunc();
		}
	}
	_callFirstLoadFunc()
	{
		if(this.firstLoadFuncCalled === false && typeof this.firstLoadFunc == 'function') {
			this.firstLoadFunc();
			this.firstLoadFuncCalled = true;
		}
	}
	_callPostLoadFunc()
	{
		if(typeof this.postLoadFunc == 'function') {
			this.postLoadFunc();
		}
	}

	build(sourceData)
	{
		this.sourceData = sourceData;
		let newHead  = this.buildHeader(this.sourceData['labels_by_field']);
		let newBody  = this.buildBody(this.sourceData['table_data']);
		let newFoot  = this.buildFoot(sourceData['labels_by_field']);
		let newTable = this.buildTable(newHead, newBody, newFoot);

		// Remove data loading and replace it with the table
		this.objTarget.html(null).html(newTable).removeClass('d-none');

		this.lastReloadTime = Date.now();

		this._callFirstLoadFunc();
		this._callPostLoadFunc();

		if(this.freezeHeader.active === true) {
			this._freezeHeader();
		}
	}

	buildTable(head, body, foot)
	{
		if(this.tableType == 'horizontal') {
			return this.buildTableHorizontal(head, body, foot);
		}
		else if(this.tableType == 'vertical') {
			return this.buildTableVertical(body);
		}

	}
	buildTableHorizontal(head, body, foot)
	{
		let newTable = this.objTableH.clone()
			.html(null)
			.addClass('table-'+this.name)
			.removeClass('d-none');
		newTable.append(head).append(body).append(foot);
		return newTable;
	}
	buildTableVertical(body)
	{
		let newTable = this.objTableV.clone()
			.html(null)
			.addClass('table-'+this.name)
			.removeClass('d-none');
		newTable.append(body);
		return newTable;
	}

	buildHeader(labelsByField)
	{
		if(this.tableType === 'vertical') {
			return null;
		}
		let newHead;
		let newHeadTRHead;
		let newHeadTH;

		if(this.freezeHeader.active === true) {
			newHead       = this.objHeadFr.clone()
			newHeadTRHead = this.objHeadFrTRHead.clone()
			newHeadTH     = this.objHeadFrTH.clone()
		} else {
			newHead       = this.objHead.clone()
			newHeadTRHead = this.objHeadTRHead.clone()
			newHeadTH     = this.objHeadTH.clone()
		}

		newHead
			.attr('id', 'table-head-'+this.name)
			.addClass('table-head-'+this.name)
			.html(null)
			.html('').removeClass('d-none');
		newHeadTRHead
			.attr('id', 'table-head-tr-'+this.name)
			.addClass('table-head-tr-'+this.name)
			.addClass('head')
			.html(null)
			.html('').removeClass('d-none');
		for(const [field, label] of Object.entries(labelsByField)) {
			let newHeadTH;
			if(this.freezeHeader.active === true) {
				newHeadTH = this.objHeadFrTH.clone()
			}
			else {
				newHeadTH = this.objHeadTH.clone()
			}
			newHeadTH
				.attr('id', 'table-head-th-'+this.name+'-'+field)
				.attr('field', field)
				.addClass('table-head-th-'+this.name)
				.addClass('table-head-th-'+this.name+'-'+field)
				.addClass('col-'+this.name+'-'+field);
			if(this.freezeHeader.active === true) {
				newHeadTH.children('div:first-child').children('span.label').text(label).addClass('click-sort');
			}
			else {
				newHeadTH.children('span.label').text(label).addClass('click-sort');
			}


			if(false == this.hideFields.includes(field)) {
				newHeadTH.removeClass('d-none');
			}

			if(this.isSortable && !this.sortFieldsOmit.includes(field)) {

				let newSortArrow = this.objSortArrowHorz.clone()
					.removeClass('d-none')
					.attr('id', 'sort-'+this.name+'-'+field);

				if(this.sortFields.hasOwnProperty(field)) {
					let direction = this.sortFields[field]['direction'];
					let priority  = this.sortFields[field]['priority'];

					newSortArrow.children('span.icon-sort-able').addClass('d-none');
					newSortArrow.children('span.icon-sort-arrow-'+direction).removeClass('d-none');
					newSortArrow.children('span.sort-priority').text(priority).removeClass('d-none');

					let self = this;

					newSortArrow.children('span.icon-sort-cancel')
						.removeClass('d-none')
						.addClass('clickme')
						.click(function(){
							self.sortClearField(field)
						});
				}
				if(this.freezeHeader.active === true) {
					newHeadTH.children('div:first-child').append(newSortArrow)
					newHeadTH.children('div').children('span.click-sort').click(this.sort.bind(this)).addClass('clickme');
				}
				else {
					newHeadTH.append(newSortArrow);
					newHeadTH.children('span.click-sort').click(this.sort.bind(this)).addClass('clickme');
				}
			}
			newHeadTRHead.append(newHeadTH);
		}
		newHead.html(newHeadTRHead)


		if(this.freezeHeader.active === true) {
			// console.log(this.sourceData['table_header_data'])
			let newHeadTRData = this.objHeadFrTRData.clone()
				.attr('id', 'table-head-tr-'+this.name)
				.addClass('table-head-tr-'+this.name)
				.addClass('data')
				.html(null)
				.html('').removeClass('d-none');
			for(let i in this.sourceData['table_header_data']) {
				for(let [field, value] of Object.entries(this.sourceData['table_header_data'][i])) {
					let newHeadTD = this.objHeadFrTD.clone();
						// .attr('id', 'table-head-th-'+this.name+'-'+field)
					newHeadTD
						.attr('field', field)
						.addClass('table-head-th-'+this.name)
						.addClass('table-head-th-'+this.name+'-'+field)
						.addClass('col-'+this.name+'-'+field);
					if(value) {
						newHeadTD.children('div').text(value);
					} else {
						newHeadTD.children('div').html('&nbsp;');
					}
					newHeadTRData.append(newHeadTD);
					if(false == this.hideFields.includes(field)) {
						newHeadTD.removeClass('d-none');
					}
				}
			}
			newHead.append(newHeadTRData);
		}
		return newHead;
	}

	buildBody(tableData)
	{
		if(this.tableType == 'horizontal') {
			return this.buildBodyHorizontal(tableData);
		}
		else if(this.tableType == 'vertical') {
			return this.buildBodyVertical(tableData);
		}
	}

	buildBodyHorizontal(tableData)
	{
		let newBody = this.objBodyH.clone()
			.html(null)
			.attr('id', 'table-body-'+this.name)
			.addClass('table-body-'+this.name)
			.removeClass('d-none');

		for(let key in tableData) {
			let rowId = (this.rowIdField) ? tableData[key][this.rowIdField] : key;
			let newBodyTR = this.objBodyTRH.clone()
				.html(null)
				.attr('id', rowId)
				.attr('row_id', rowId)
				.data('index', key)
				.addClass('table-body-tr-'+this.name)
				.removeClass('d-none');

			for (const field in this.sourceData['labels_by_field']) {
				let value = tableData[key][field];
				let newBodyTD = this.objBodyTDH.clone().html(null)
					.attr('field', field)
					.attr('id', 'cell-'+this.name+'-'+field+'-'+rowId)
					.addClass('cell-'+field)
					.addClass('cell-'+this.name+'-'+field)
					.addClass('col-'+this.name+'-'+field);
					// .html(value);

				if (typeof this.onRenderCell[field] === 'function') {
					newBodyTD = this.onRenderCell[field](newBodyTD, key, tableData[key]);
				} else {
					newBodyTD.text(tableData[key][field] ?? '');
				}

				if(false == this.hideFields.includes(field)) {
					newBodyTD.removeClass('d-none');
				}
				newBodyTR.append(newBodyTD);
			}
			newBody.append(newBodyTR);
		}

		return newBody;
	}

	buildBodyVertical(tableData)
	{
		// Check to make sure that the number of rows is less than the max allowed
		if(this.tableTypeVerticalMaxRows < tableData.length) {
			throw new Error(
				'The record count ['
				+tableData.length+
				'] is greater than the maximum allowed ['
				+this.tableTypeVerticalMaxRows+
				'] for a vertical table type.');
		}

		let newBody = this.objBodyV.clone()
			.html(null)
			.attr('id', 'table-body-'+this.name)
			.addClass('table-body-'+this.name)
			.removeClass('d-none');

		// Restructure the rows into columns
		let rows = {};

		// Label Column
		for(const [field, label] of Object.entries(this.sourceData['labels_by_field'])) {
			rows[field] = {label:label, rowId:null, items:[]};
		}


		for(let key in tableData) {
			let rowId = (this.rowIdField) ? tableData[key][this.rowIdField] : key;
			let row = tableData[key];
			for(const [field, value] of Object.entries(row)) {
				rows[field].items.push(value);
			}
		}

		let rowIndex = 0;
		for(const field in rows) {
			let rowId     = rows[field].rowId;
			let newBodyTR = this.objBodyTRV.clone()
				.html(null)
				.attr('id', rowId)
				.attr('row_id', rowId)
				.data('index', rowIndex++)
				.addClass('table-body-tr-'+this.name)
				.removeClass('d-none');

			let firstRow = true;
			for(const key in rows[field].items) {

				if(firstRow === true) {
					let newBodyTDLabel = this.objBodyTDVLabel.clone()
						.attr('field', field)
						.attr('id', 'cell-'+this.name+'-'+field+'-'+rowId)
						.addClass('cell-label')
						.addClass('label-'+field)
						.addClass('label-'+this.name+'-'+field);
						// .removeClass('d-none');

					if(false == this.hideFields.includes(field)) {
						newBodyTDLabel.removeClass('d-none');
					}

					newBodyTDLabel.children('span.label').text(rows[field].label).addClass('click-sort');

					newBodyTR.append(newBodyTDLabel)
					firstRow = false;

					if(this.isSortable && !this.sortFieldsOmit.includes(field)) {

						let newSortArrow = this.objSortArrowVert.clone()
							.removeClass('d-none')
							.attr('id', 'sort-'+this.name+'-'+field);

						if(this.sortFields.hasOwnProperty(field)) {
							let direction = this.sortFields[field]['direction'];
							let priority  = this.sortFields[field]['priority'];

							newSortArrow.children('span.icon-sort-able').addClass('d-none');
							newSortArrow.children('span.icon-sort-arrow-'+direction).removeClass('d-none');
							newSortArrow.children('span.sort-priority').text(priority).removeClass('d-none');

							let self = this;

							newSortArrow.children('span.icon-sort-cancel')
								.removeClass('d-none')
								.addClass('clickme')
								.click(function(){
									self.sortClearField(field)
								});
						}
						newBodyTDLabel.append(newSortArrow);
						newBodyTDLabel.children('span.click-sort').click(this.sort.bind(this)).addClass('clickme');
					}

				}

				let value = rows[field].items[key];
				let newBodyTD = this.objBodyTDVValue.clone().html(null)
					.attr('field', field)
					.attr('id', 'cell-'+this.name+'-'+field+'-'+rowId)
					.addClass('cell-data')
					.addClass('cell-'+field)
					.addClass('cell-'+this.name+'-'+field)
					.addClass('col-'+this.name+'-'+field);
					// .removeClass('d-none');
					// .html(value);

				if (typeof this.onRenderCell[field] === 'function') {
					newBodyTD = this.onRenderCell[field](newBodyTD, key, tableData[key]);
				} else {
					newBodyTD.text(value ?? '');
				}
				if(false == this.hideFields.includes(field)) {
					newBodyTD.removeClass('d-none');
				}
				newBodyTR.append(newBodyTD);
			}
			newBody.append(newBodyTR);
		}

		return newBody;
	}

	buildFoot(tableData) {
		if(this.tableType == 'vertical') {
			return null;
		}
		return this.objFoot.clone();
	}

	_freezeHeader()
	{
		// Adjust the CSS to allow for table cell size manipulation
		let tableName = this.name;
		let table = $('table.table-'+this.name);

		this.objTarget.addClass('ebrtable-container-freeze-header').css({
			'height'         : this.freezeHeader.tableHeight
		});

		table.addClass('ebrtable-table-freeze-header').css({
			 'width' : this.freezeHeader.tableWidth
		});

		if(typeof postFreeze == 'function') {
			postFreeze();
		}
	}

	sort()
	{
		let field = $(event.target).closest('th,td').attr('field')

		if(false == this.sortFields.hasOwnProperty(field)) {
			this.sortFields[field] = {'direction':null, 'priority':null};
		}

		let isASC = (this.sortFields.hasOwnProperty(field) && this.sortFields[field]['direction'] == 'asc');
		this.sortFields[field]['direction'] = isASC ? 'desc' : 'asc';

		let priority = 1;
		Object.entries(this.sortFields).forEach(([field, config]) => {
			this.sortFields[field]['priority'] = priority;
			priority++;
		});

		this._sort(true);
	}


	sortClearAll()
	{

	}

	sortClearField(field)
	{
		delete this.sortFields[field];
		let priority = 1;
		Object.entries(this.sortFields).forEach(([field, config]) => {
			this.sortFields[field]['priority'] = priority;
			priority++;
		});
		this._sort(true);
	}

	_sort(reload=false)
	{
		this.sourceData['table_data'].sort((a, b) => {
			for(let field in this.sortFields) {
				let direction = this.sortFields[field]['direction']
				let valueA    = a[field];
				let valueB    = b[field];

				// If the column type is number, convert to number
				if(String(Number(valueA)) == String(valueA)) {
					valueA = Number(valueA);
					valueB = Number(valueB);
				}

				// Sort Order
				if(direction === 'asc') {
					if (valueA < valueB) return -1;
					if (valueA > valueB) return 1;
				}
				else {
					if (valueA < valueB) return 1;
					if (valueA > valueB) return -1;
				}
			};
			return 0;  // If all columns match, they are considered equal
		});
		if(reload == true) {
			this._load(true);
		}
	}


	rowCount()
	{
		return this.sourceData['row_total'];
	}
}