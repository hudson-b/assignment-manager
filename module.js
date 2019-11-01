// -------------------------------------
var Module = {

    "onReady" : function() {


             // Display the project version number
             $("#version").load("VERSION");

             // Handle the main navbar links
             $("#link-log").click( function() { Module.showLog(); }  );
             $("#link-rubrics").click( function() { Module.showLog(); }  );


             // Load a classroom
             $("#link-classrooms-dropdown").on('click', function(e) {
                  link = e.target;
                  classroomID = $(link).data('classroomID');
                  Module.showClassroom ( classroomID );
             });
                   
             // Fetch the data and populate the selector
             Module.fetchData().then( Module.showClassrooms );
 
   },


  "icon" : function( icon ) {
    return $('<i></i>', { "class" : icon } );
   }, 


  "fetchData" : function() {
         return $.ajax({
           "url" : "index.php",
           "method" : "OPTIONS",
           "dataType" : "json",
           "success" : function(d) { Module['Data'] = d; }
         });
   },



  "showLog" : function() {
        container = $("#page-content");
        container.empty();
        $('<h2></h2>').html('System Log').appendTo(container);
        table =  Module.entityTable('log').addClass('small').appendTo( container );
   },


  "showClassrooms" : function() {

         data= Module['Data'];

         container = $("#link-classrooms-dropdown");
         container.empty();

         $.each( data['classrooms'], function( classroomID, classroomRecord ) {

            a=$('<a></a>', { "class" : "dropdown-item", "href" : "#" } )
              .data( 'classroomID', classroomRecord['id'] )
              .text( classroomRecord['name']  )
              .appendTo( container );

         });
   },
         

  "showClassroom" : function( classroomID ) {

        container = $("#page-content");
        container.empty();

        classroomRecord = Module['Data']['classrooms'][classroomID];
 
        $('<h3></h3>').html( classroomRecord['name'] ).appendTo(container);

        nav = $('<ul></ul>', { "id" : "classroom-nav", "class" : "nav nav-tabs" } ).appendTo( container );
        content = $('<div></div>', { "class" : "tab-content" } ).appendTo( container );

        // Build a data collection for this classroom

        // Show the tabs
        items = { 
           'submissions' : { 'caption':'Submissions', 'icon' : 'fas fa-paperclip', 'schema' : 'bySubmission' },
           'students' :  { 'caption':'Students', 'icon' : 'fas fa-user-friends', 'schema' : 'byStudent'  },
           'assignments' :  { 'caption':'Assignments', 'icon' : 'fas fa-scroll', 'schema' : 'byAssignment' },
           'gradebool' : { 'caption' : 'Gradebook', 'icon' : 'fas fa-book-open' }
        }

        $.each( items, function( itemKey, itemConfig ) {

            // Build the tab
            li=$('<li></li>', { "class" : "nav-item" } ).appendTo( nav ); 
            a=$('<a></a>', { "class" : "nav-link classroom-item", "href" : "#classroom-" + itemKey }).appendTo( li );
            caption=$('<span></span>').html( '&nbsp;' + itemConfig['caption'] + '&nbsp;&nbsp;' ).appendTo( a );
            icon=$('<i></i>', { "class" : itemConfig['icon'] } ).prependTo( caption );
      
            // Build the pane
            pane = $('<div></div>', { "id" : "classroom-" + itemKey, "class" : "tab-pane show" } ).appendTo( content );

            // Build the content
            if( itemConfig['schema'] ) {
                table=$('<table></table>', { "class" : "table small", "width":"100%" }).appendTo( pane );
                Module.schemaTable( table, itemConfig['schema'], data[itemKey] );
                // info=$('<span></span>', { "class" : "badge badge-xs badge-secondary float-right"} ).html( items.length ).appendTo( a );
            }


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


   "schemaTable" : function( table, schemaKey, dataCollection ) {

        schema = Module.Schema[ schemaKey ] || false;
        if( ! schema ) return;

        data = [];
        $.each( dataCollection, function( dataKey, dataRecord ) {
                data.push( dataRecord );
        });
      
        //container = $('<div></div>');
        // table = $('<table></table>', {'class' : 'table small', 'width' : '100%' } ).appendTo(container);

        config={
             "dom" : 'Bftip',
             "paging" : true,
             "pageLength" : 10,
             "select" : "os",
             "data" : data,
             "columns" : [],
             "buttons" : ['csv']
        }

        config = $.extend( config, schema['table'] || {} );
        config['select'] = true;


        if( config['XXaltEditor'] === true ) {
          config['select'] = "os";
          config['dom'] = 'B'+config['dom'];
          config['buttons'].push(  {  "name" : "add",                           "text" : "Add "} );
          config['buttons'].push(  {  "name" : "edit",   "extend" : "selected", "text" : "Edit" } );
          config['buttons'].push(  {  "name" : "delete", "extend" : "selected", "text" : "Delete" } );
        }

        console.log( config );

        table.DataTable( config );
        return table;

    }

}


// ----------------------------


// After all is loaded...
$(document).ready(  function() { Module.onReady(); });

