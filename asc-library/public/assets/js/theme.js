(function(){
	const cards=document.querySelectorAll('.hover-rise');
	cards.forEach(c=>{
		c.addEventListener('mousemove', (e)=>{
			const r=c.getBoundingClientRect();
			const x=e.clientX - r.left; const y=e.clientY - r.top;
			c.style.setProperty('--mx', x+'px');
			c.style.setProperty('--my', y+'px');
		});
	});
})();