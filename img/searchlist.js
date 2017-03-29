function getCheckedIds(form, from_class, to_name)
{
	var ids = '';
	var elms = document.getElementsByClassName(from_class);
	for(var i=0; i<elms.length; ++i)
	{
		if(elms[i].checked)
			ids += '@' + elms[i].value;
	}
	ids = ids.replace(/^@/, '');
	var elms = form.getElementsByTagName('input');
	for(var i=0; i<elms.length; ++i)
	{
		if(elms[i].name==to_name)
		{
			elms[i].value = ids;
			return;
		}
	}
	alert('IDを指定できません');
	return;
}
