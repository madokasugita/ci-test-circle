function TimeoutChecker() 
{
	this.initialize.apply(this, arguments);
}
TimeoutChecker.prototype = 
{
	initialize: function() 
	{

	}
,
	run: function(interval, target)
	{
		this.interval = interval;
		if(!target) target = document.body;

		me = this;
		target.onmousemove = function(){
			me.onMouseMove();
			me.reset();
		}
		target.onkeydown = function(){
			me.onKeyDown();
			me.reset();
		}
		this.reset();
	}
,
	stop: function()
	{
		if(this.timer) clearTimeout(this.timer);
	}
,
	reset: function()
	{
		this.stop();
		this.timer = setTimeout(this.onTimeout, this.interval)
	}
,
	onMouseMove: function(){}
,
	onKeyDown: function(){}
,
	onTimeout: function(){}
}