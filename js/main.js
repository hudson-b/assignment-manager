

$(document).ready(function(){

  $('.header').height($(window).height());

  $('table').DataTable({
       "columns" : [
            { "data" : "someThing" , "title" : "Some Thing" },
            { "data" : "anotherThing" , "title" : "Another Thing" }
       ]
       
  });


});


