window.NumberFormat = {

	 _isBlank: function(value) {
		 // String(value).trim() over value === '' in order to catch things like &nbsp;
		return value === null || value === undefined || String(value).trim() === '';
	 }

	,init: function() {
	 	$('.money').each(function(){
	 		var amount = $(this).attr('money_value');
	 		var sign = (amount == 0) ? 'zero' : (amount > 0) ? 'positive' : 'negative';
	 		$(this).addClass('money-'+sign);
	 	});

	 	$('.performance').each(function(){
	 		var ratio = $(this).attr('performance_value');
	 		var sign = (ratio == 0) ? 'zero' : (ratio >= 1) ? 'positive' : 'negative';
	 		$(this).addClass('money-'+sign);
	 	});
	 }

	,USD: function(amount, showCents = true)
	{
		if(this._isBlank(amount) || (typeof amount !== 'number' && isNaN(amount))) {
			return amount;
		}
		const formatter = new Intl.NumberFormat('en-US', {
			 style: 'currency'
			,currency: 'USD'
			,maximumFractionDigits: showCents ? 2 : 0
		});
		return formatter.format(amount)
	}

	,USDK: function(amount)
	{
		if(this._isBlank(amount) || (typeof amount !== 'number' && isNaN(amount))) {
			return amount;
		}
		amount = Math.round(amount / 1000);
		const formatter = new Intl.NumberFormat('en-US', {
			 style: 'currency'
			,currency: 'USD'
			,maximumFractionDigits: 0
		});


		if(amount < 0) {
			var formatted = formatter.format(Math.abs(amount))+'K'
			return '('+formatted+')';
		}
		var formatted = formatter.format(amount)+'K'
		return formatted;
	}

	,Percent0: function(ratio)
	{
		if(this._isBlank(ratio)) {
			return ratio;
		}
		const formatter = new Intl.NumberFormat('en-US', { style: 'percent', maximumFractionDigits: 0 });
		return formatter.format(ratio);
	}

	,Percent: function(ratio, decimal)
	{
		if(this._isBlank(ratio)) {
			return ratio;
		}
		const formatter = new Intl.NumberFormat('en-US', { style: 'percent', maximumFractionDigits: decimal });
		return formatter.format(ratio);
	}
}