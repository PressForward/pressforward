console.log('Nominate This Setup');
//Coming in:
//var d=document,
//w=window,
//e=w.getSelection,
//k=d.getSelection,
//x=d.selection,
//s=(e?e():(k)?k():(x?x.createRange().text:0)),
//l=d.location,
//e=encodeURIComponent,
//

window.pfnt = {};
window.pfnt.windows = {};
window.pfnt.styles = {};
window.pfnt.tools = {};

function pfnt_activate(){

	var pf = window.pfnt;
	var windows = window.pfnt.windows;
	var pf_styles = window.pfnt.styles;
	var pf_tools = window.pfnt.tools;

	windows.mainWindow = document.createElement('div');

	windows.mainWindow.setAttribute('id', 'pressforward-nt');

	windows.innerWindow = document.createElement('div');

	windows.innerWindow.setAttribute('id', 'pressforward-nt__inner');

	pf_styles.mwStyles = 'position: absolute;'+
						    'width: 50%;'+
						    'height: 450px;'+
						    'display: block;'+
						    'background: #c7c7c7;'+
						    'top: 0;'+
						    'left: 25%;'+
							'padding: 1px;'+
						    'box-sizing: border-box;'+
						    'z-index: 10000;';

	pf_styles.iwStyles = 'position: relative;'+
						    'width: 100%;'+
						    'height: 100%;'+
						    'background: white;'+
						    'border: #afceaf 3px solid;'+
						    'box-sizing: border-box;';

	windows.mainWindow.setAttribute('style', pf_styles.mwStyles);

	windows.innerWindow.setAttribute('style', pf_styles.iwStyles);

	var pfMainWindowAppender = function(){ document.getElementsByTagName('body')[0].appendChild(window.pfnt.windows.mainWindow); };
	var pfInnerWindowAppender = function(){ window.pfnt.windows.mainWindow.appendChild(window.pfnt.windows.innerWindow); };

	pfMainWindowAppender();
	pfInnerWindowAppender();

};

pfnt_activate();
