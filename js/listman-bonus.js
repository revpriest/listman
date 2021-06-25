function copyUrl(url){
  url = window.location.href;
	var end = url.indexOf("?");
  if(end>0){
    url = url.substring(0,end);
	}
  navigator.clipboard.writeText(url);
  alert("Url Copied to clipboard:\n"+url);
}
var moo = document.getElementById("clipcopy");
moo.onclick = copyUrl;
