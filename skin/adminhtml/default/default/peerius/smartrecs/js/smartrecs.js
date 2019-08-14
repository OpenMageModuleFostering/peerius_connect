	var enAllSelect = document.getElementById("peerius_general_enableall");
	enAllSelect.addEventListener("change", enableDisable);
	function enableDisable(e){
	 	alert(enAllSelect.options[enAllSelect.selectedIndex].value);
		//var peeriusMenuElem = document.getElementById("peerius_home-head").parentNode.parentNode;
		//peeriusMenuElem.style.display = 'none';

	}



