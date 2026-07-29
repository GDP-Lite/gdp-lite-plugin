document.addEventListener('click',function(event){
	var tabButton=event.target.closest('.gdp-meta-nav button');
	if(tabButton){
		var wrap=tabButton.closest('.gdp-meta-tabs');
		wrap.querySelectorAll('.gdp-meta-nav button').forEach(function(item){item.classList.remove('is-active');});
		wrap.querySelectorAll('.gdp-meta-panel').forEach(function(item){item.classList.remove('is-active');});
		tabButton.classList.add('is-active');
		var panel=wrap.querySelector('[data-panel="'+tabButton.dataset.tab+'"]');
		if(panel){panel.classList.add('is-active');}
		return;
	}
	var copyButton=event.target.closest('.gdp-copy');
	if(copyButton){
		var text=copyButton.getAttribute('data-copy')||'';
		navigator.clipboard.writeText(text).then(function(){
			var label=copyButton.querySelector('.gdp-copy-label');
			if(!label){return;}
			var old=label.textContent;
			label.textContent=(window.gdpLiteAdmin&&gdpLiteAdmin.copied)?gdpLiteAdmin.copied:'Copied';
			copyButton.classList.add('is-copied');
			window.setTimeout(function(){label.textContent=old;copyButton.classList.remove('is-copied');},1600);
		});
	}
});
