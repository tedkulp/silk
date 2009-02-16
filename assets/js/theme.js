$(document).ready(function() {


        //textareas
        //$('textarea.resizable:not(.processed)').TextAreaResizer();
	 	
	 	  //tablesort
         $('.silksorter')
         .tablesorter({widthFixed: true, widgets: ['zebra']})
         
           // Page error
	  $('.pageerror').animate({
        backgroundColor: '#FFE3DD'
      }, 500 );
          $('.pageerror').animate({
        backgroundColor: '#DACA05'
      }, 500 );
	  $('.pageerror').animate({
        backgroundColor: '#FFE3DD'
      }, 500 );
      
      // flash() messages 
	   $('#flash').animate({
        backgroundColor: '#FF9B00'
      }, 500 );
          $('#flash').animate({
        backgroundColor: '#FFFFFF'
      }, 500 );
	  $('#flash').animate({
        backgroundColor: '#A5A58F'
      }, 500 );
      
	

       //extrenal links
      //$("a[@href^=http]").each(function() {
      //  if(this.href.indexOf(location.hostname) == -1) {
      //$(this).click(function(){window.open(this.href);return false;});
    //}
  //});







});//end ready


  
