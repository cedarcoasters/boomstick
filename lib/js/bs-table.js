import {Table} from '/js/table.js';


export class BSTable {

	__validate_definition__()
	{
		this.__required__methods__ = {
			 'sourceURL': 'Returns a URL for the source of the table data.'
			,'postLoad' : 'Method that is called after the table data has been loaded or reloaded.'
		};

		let messages = [];
		for(const [method, description] of Object.entries(this.__required__methods__)) {
			let className = this.constructor.name;
			let type      = typeof this[method];


			if(this[method] && type === 'function') {
				continue;
			}
			else if(this[method] && type !== 'function') {
				messages.push('The ['+className+'::'+method+'(type:'+type+')] must a function()  ('+description+')');
			}
			messages.push('The class ['+className+'] must have a method ['+method+'] defined. ('+description+')');
		}
		if(messages.length > 0) {
			throw new Error("\n\n"+messages.join("\n")+"\n\n");
		}
	}
	__setOptionArray(option, values)
	{
		if(typeof values === 'string') {
			this.table[option] = [values];
			return true;
		}
		else if(typeof values === 'object') {
			this.table[option] = values
			return true;
		}
	}
	constructor(name, skipLoading=false,  template='div#ebr-table-template')
	{
		this.__validate_definition__();

		this.name  = name;
		this.table = new Table(name, template, 'div#table-'+name, skipLoading);

		this.table.isSortable    = false;
		this.table.rowIdField    = 'id';
		this.table.preLoadFunc  = (typeof this.preLoad === 'function') ? this.preLoad : function(){};
		this.table.postLoadFunc  = this.postLoad;
		this.table.firstLoadFunc = (typeof this.firstLoad === 'function') ? this.firstLoad : function(){};
	}

	async load({withIndicator = false} = {})
	{
		const wi = withIndicator ?? false;
		this.table.sourceURL    = this.sourceURL();
		this.table.filterParams = this.filterParams();
		return this.table.load({withIndicator: wi});
	}
	async reload()
	{
		this.table.sourceURL    = this.sourceURL();
		this.table.filterParams = this.filterParams();
		return this.table.reload();
	}

	build(sourceData)
	{
		this.table.build(sourceData);
	}

	clear()
	{
		this.table.clear();
	}

	filterParams()
	{
		return null;
	}

	setMethodPOST()
	{
		this.table.sourceMethod = 'POST';
	}
	setMethodGET()
	{
		this.table.sourceMethod = 'GET'; // default
	}

	/**
	 * Configuration option methods
	 */
	isSortable()
	{
		this.table.isSortable = true;
		return true;
	}
	notSortable()
	{
		this.table.isSortable = false;
		return true;
	}
	setTypeHorizontal()
	{
		this.table.tableType = 'horizontal';
	}
	setTypeVertical()
	{
		this.table.tableType = 'vertical';
	}
	setRowIdField(field)
	{
		this.table.rowIdField = field;
		return true;
	}
	setHideFields(fields)
	{
		this.__setOptionArray('hideFields', fields);
	}
	setColumnOrder(fields)
	{
		this.__setOptionArray('columnOrder', fields);
	}
	setReloadFreqms(milliseconds)
	{
		this.table.reloadFreqms = milliseconds;
	}
	freezeHeader({tableWidth='100%', tableHeight='400px', atRow=0, postFreeze=null} = {})
	{
		this.table.freezeHeader.active      = true;
		this.table.freezeHeader.tableHeight = tableHeight;
		this.table.freezeHeader.tableWidth  = tableWidth;
		this.table.freezeHeader.atRow       = atRow;
		this.table.freezeHeader.postFreeze  = null;
	}


	/**
	 * Table data retrieval methods
	 */
	rowCount()
	{
		return this.table.sourceData['row_count']; // keeping it around in case someone/something is using this
	}
	rowsPageCount()
	{
		return this.table.sourceData['row_count'];
	}
	rowsTotal()
	{
		return this.table.sourceData['row_total'];
	}
	lastReloadTime()
	{
		return this.table.lastReloadTime;
	}
	sourceOptions()
	{
		return this.table.sourceOptions;
	}
	fieldLength(field)
	{
		return this.table.sourceData['lengths_by_field'][field];
	}

	/**
	 * Table Data Manipulation
	 */
	setCellValue(id, field, value)
	{
		for(let key in this.table.sourceData['table_data']) {
			if(this.table.sourceData['table_data'][key][this.table.rowIdField] == id) {
				this.table.sourceData['table_data'][key][field] = value;
			}
		}
	}
	addDataRow(row)
	{
		this.table.sourceData['table_data'].unshift(row);
	}


	makeEditable()
	{
		let thisTable  = this;
		let options    = thisTable.sourceOptions();
		let editFields = options['edit_fields'];

		for(let key in editFields) {
			let field      = editFields[key];
			let cellPrefix = 'td.cell-'+this.name+'-';

			$(cellPrefix+field).each(function(){
				thisTable.setCellEdit(this, field);
			});
			$(cellPrefix+field).children('input').on('blur keydown', function(event) {
				if (event.type === 'blur') {
					thisTable.saveCell(event);
				}
				else if (event.type === 'keydown' && event.key === 'Enter') {
					thisTable.saveCell(event);
				}
			});
			$(cellPrefix+field).children('select').on('change', function(event){
				thisTable.saveCell(event);
			});
		}

		thisTable.addNewFormRow();
	}

	allowDeleteSingle(clickHandler)
	{
		let thead     = $('thead#table-head-'+this.name+'>tr');
		let tbodyRows = $('tbody#table-body-'+this.name+'>tr');

		let deleteTH = this.table.objHeadTH.clone().html('').removeClass('d-none');
		thead.prepend(deleteTH);

		let _this     = this;
		let thisTable = this.table;
		$.each(tbodyRows, function(index, row) {
			let rowId = $(row).attr('id');
			if(rowId == 'new') {
				$(row).prepend('<td></td>');
				return;
			}
			let span = $('<span class="clickme material-icons">delete</span>')
				.addClass('td-delete-'+_this.name)
				.attr('id', rowId)
				.click(function(){
					$(this).off().addClass('text-danger').click(clickHandler)
				});

			let deleteTD = thisTable.objBodyTD.clone()
				.html(span)
				.removeClass('d-none')
				.addClass('cell-delete cell-delete-'+_this.name);

			$(row).prepend(deleteTD);
		})


	}

	addNewFormRow()
	{
		let tbody  = $('tbody#table-body-'+this.name);
		let newRow = tbody.children('tr').first().clone().attr('id', 'new');

		newRow.children('td').each(function() {
			let td = $(this).attr('id', 'new');

			if(td.find('input, select').length > 0) {
				let input  = $(this).children('input').val(null).addClass('new-row-form');
				let select = $(this).children('select').addClass('new-row-form').children('option').each(function(){
					$(this).attr('selected', null);
				});
			}
			else {
				td.html('<span class="material-icons">add</span>');
			}
		});
		tbody.prepend(newRow);

		let thisTable = this;
		$(':input.new-row-form:not(.d-none)').on('change', function(){
			thisTable.saveNewRow();
		});
	}


	saveNewRow()
	{
		$(':input.new-row-form:not(.d-none)').each(function(){
			$(this).removeClass('data-saved data-error').addClass('data-unsaved');
		});
		let formValues = {};
		let csrfName  = $('div#ebr-table-edit-parts>form>input:first').attr('name');
		let csrfValue = $('div#ebr-table-edit-parts>form>input:first').val();
		formValues[csrfName] = csrfValue;
		$(':input.new-row-form:not(.d-none)').each(function(){
			let fieldName  = $(this).attr('name');
			let fieldValue = $(this).val();
			formValues[fieldName] = fieldValue;
		});

		let url       = this.saveNewURL();
		let thisTable = this;
		$.post(url, formValues	, function(response){
			thisTable.reload();
		})
		.fail(function(response){
			$(':input.new-row-form:not(.d-none)').each(function(){
				$(this).removeClass('data-saved data-unsaved data-error');
			});
			$.each(response['responseJSON']['errors'], function(field, messages){
				$(':input.new-row-form:not(.d-none)[name="'+field+'"]')
					.removeClass('data-saved data-unsaved').addClass('data-error');
			})
		});

	}

	setCellEdit(cellTag, field)
	{
		let fieldConfig = this.sourceOptions()['form_options'][field];
		let fieldType   = (fieldConfig['type']) ? fieldConfig['type'] : 'text';
		let cell        = $(cellTag);
		let value       = cell.text();

		if(fieldType == 'text') {
			let input = $('div#ebr-table-edit-parts>form>input.table-edit-input-text')
				.clone()
				.val(value)
				.attr('field', field)
				.attr('name', field)
				.css('width', ( this.fieldLength(field)+2)+'ch');
			cell.html(input);
			let inputPrev = $('div#ebr-table-edit-parts>form>input.table-edit-input-text-previous-value')
				.clone()
				.val(value);
			cell.append(inputPrev);
		}
		else if(fieldType = 'select' && fieldConfig['options']) {
			let fieldOptions = fieldConfig['options'];
			let select = $('div#ebr-table-edit-parts>form>select.table-edit-select').clone()
				.attr('field', field)
				.attr('name', field);
			let _option = select.children('option');
			$.each(fieldOptions, function(id, mga_name){
				let option = _option.clone().val(id).text(mga_name)
				if(id == value) {
					option.attr('selected', true);
				}
				select.append(option);
			})
			cell.html(null).append(select);
		}
	}

	blurCell(event)
	{
		let inputCurrent  = $(event.target);
		let inputPrevious = $(inputCurrent).siblings().last();
		if(inputCurrent.val() != inputPrevious.val()) {
			inputCurrent.removeClass('data-saved data-error').addClass('data-unsaved');
		}
		else {
			inputCurrent.removeClass('data-saved data-error data-unsaved')
		}
	}


	saveCell(event)
	{
		let inputEdit = $(event.target);
		let field     = inputEdit.attr('field');
		let newValue  = inputEdit.val();
		let recordId  = inputEdit.parent().parent().attr('id');
		let postData  = {
			'_token' : $('div#ebr-table-edit-parts>form>input:first').val()
		};
		postData[field] = inputEdit.val()

		let url = this.saveCellURL()
			.replace('ph-id', recordId)

		let thisTable = this;
		$.post(url, postData, function(){
			let inputPrevious = inputEdit.siblings().last();
			inputPrevious.val(newValue);

			inputEdit.removeClass('data-error data-unsaved').addClass('data-saved');
			thisTable.setCellValue(recordId, field, newValue);
			setTimeout(function(){
				inputEdit.removeClass('data-saved');
			}, 700)
		}).fail(function(){
			inputEdit.removeClass('data-saved data-unsaved').addClass('data-error');
		});
	}
}
