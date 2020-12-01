//background.js
chrome.management.getSelf(extension => {
		
    chrome.runtime.onMessage.addListener(  function (request, sender, sendResponse)  {		
		
		var f = function (msg) { 
			console.log(msg);
			return sendResponse(msg)
		};		
	
		f({farewell: 'ok'});		
	
    })
})