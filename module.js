// -------------------------------------
var Module = {

    "onReady" : function() {


             // Display the project version number
             $("#version").load("VERSION");

             // Handle the main navbar links
             $("#link-log").click( function() { Module.showLog(); }  );
             $("#link-rubrics").click( function() { Module.showLog(); }  );

             // Fetch the data and populate the selector
             Module.fetchData( { "classrooms" : "" } )
                .then( function(d) {
                   Module.showClassrooms(d);
                });

 
   },


  "icon" : function( icon ) {
    return $('<i></i>', { "class" : icon } );
   }, 


  "fetchData" : function( filterObject ) {

          filterParms = $.param( filterObject );

          return $.ajax({
                    "url" : "index.php?" + filterParms,
                    "method" : "OPTIONS",
                    "dataType" : "json"
          });
   },



  "showLog" : function() {
        container = $("#page-content");
        container.empty();
        $('<h2></h2>').html('System Log').appendTo(container);
        table =  $('<table></table>').addClass('small').appendTo( container );
        table = Module.schemaTable('log', table )

   },
 

  "showClassrooms" : function( records ) {

       container = $("#link-classrooms-dropdown");
       container.empty();

       $.each( records, function( i, record ) {

           classroomID = record['id'];
           classroomName = record['name'];

           a=$('<a></a>', { "class" : "dropdown-item classroom-selector", "href" : "#" } )
              .data( 'classroom' , record )
              .html( '<i class="fas fa-book"></i>&nbsp;' + classroomName )
              .appendTo( container );

       });

       $("a.classroom-selector").click( function(e) {
           e.preventDefault();
           classroom=$(this).data('classroom');
           Module.showClassroom( classroom );
       });

     
   },
         

  "showClassroom" : function( classroom ) {

        container = $("#page-content");
        container.empty();

        // Title.
        title = $('<h5></h5>', { "class" : "text-center" } ).appendTo(container);
        title.append( classroom['name'] );;
 
        nav = $('<ul></ul>', { "id" : "classroom-nav", "class" : "nav nav-tabs" } ).appendTo( container );
        content = $('<div></div>', { "class" : "tab-content pad-top" } ).appendTo( container );

       // Show the tabs
        items = { 
           'submissions' : { 'caption' : 'Submissions', 'icon' : 'fas fa-paperclip'},
           'gradebook' :   { 'caption' : 'Gradebook',   'icon' : 'fas fa-book-open' }
        }

        $.each( items, function( itemKey, itemConfig ) {

            // Build the tab
            li=$('<li></li>', { "class" : "nav-item" } ).appendTo( nav ); 
            a=$('<a></a>', { "class" : "nav-link classroom-item", "href" : "#classroom-" + itemKey }).appendTo( li );
            caption=$('<span></span>').html( '&nbsp;' + itemConfig['caption'] + '&nbsp;&nbsp;' ).appendTo( a );
            icon=$('<i></i>', { "class" : itemConfig['icon'] } ).prependTo( caption );
      
            // Build the pane
            pane = $('<div></div>', { "id" : "classroom-" + itemKey, "class" : "tab-pane show" } ).appendTo( content );

            // Build the submission table
            config = Module.Schema[ itemKey ];
            if( config ) {
               table=$('<table></table>', { "class" : "table small", "width":"100%" }).appendTo( pane );
               Module.schemaTable( itemKey, table, { "classroom" : classroom['id'] });
            }
            // info=$('<span></span>', { "class" : "badge badge-xs badge-secondary float-right"} ).html( items.length ).appendTo( a );

         });

        // Set up handlers
        $('a.classroom-item').on('click', function(e) {
                e.preventDefault();
                $(this).tab('show');
        });

        // Click the first one
        $('a.classroom-item').first().click();

             
        //$('<a></a>', { "class" : "nav-item nav-link" }).html('<i class="fas fa-table"></i> Gradebook' ).appendTo(nav);
        //table = Module.createTable( 'submissions', classroomID );
        //table.appendTo( container );

    },



   "showSubmission" : function( submissionID ) {

        Module.fetchData( { "submission" : submissionID, 'content' : true } )
        .then( function(d) {

               record = d[0];

               content = record['submission']['files'][0]['content'];

               title = '<h2>' + record['student']['last_name'] + ', ' +  record['student']['first_name'] + '</h2>' +
                       '<h5>' + record['assignment']['name'] + '</h5>';

               bootbox.dialog({
                   "title" : title,
                   "size" : "extra-large",
                   "message" : '<pre class="max-height-300">' + content + '</pre>',
                   "buttons" : {
                        "grade" : { "label" : "Grade",   "className" : "btn btn-success" }
                    }

               });
        });

   },
 


   "schemaTable" : function( schemaKey, tableObject, fetchFilter ) {

        schema = Module.Schema[ schemaKey ] || false;
        if( ! schema ) return;
     
        if( ! tableObject ) {
              container = $('<div></div>');
              tableObject = $('<table></table>', {'class' : 'table small', 'width' : '100%' } ).appendTo(container);
        }

        config={
             "dom" : 'Bftip',
             "paging" : true,
             "pageLength" : 10,
             "select" : "os",
             "columns" : [],
             "buttons" : ['csv']
        }
        config = $.extend( config, schema || {} );

        if( fetchFilter ) {
               config['ajax'] = function( data, callback, settings ) {
                 Module.fetchData( fetchFilter )
                       .then( function(d) {   callback( {'data' : d }); } );
               }
        }

        if( config['XXaltEditor'] === true ) {
          config['select'] = "os";
          config['dom'] = 'B'+config['dom'];
          config['buttons'].push(  {  "name" : "add",                           "text" : "Add "} );
          config['buttons'].push(  {  "name" : "edit",   "extend" : "selected", "text" : "Edit" } );
          config['buttons'].push(  {  "name" : "delete", "extend" : "selected", "text" : "Delete" } );
        }


        tableObject.DataTable( config );
        return container || tableObject;

    }

}


// ----------------------------


// After all is loaded...
$(document).ready(  function() { Module.onReady(); });

