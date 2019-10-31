// -------------------------------------
var Module = {

    "onReady" : function() {

             // Display the project version number
             $("#version").load("VERSION");

             Module.showClassrooms();

             $("#classroom-options").click( function(e) { 
                target=e.target;
                record=$(target).data('record'); 
                Module.showClassroom(record);
              });

             $("#link-log").click( function() { Module.showLog(); }  );
   
                   
    },


   "showClassrooms" : function( classroomRecords) {
         if( ! classroomRecords ) {
               url = Module.Schema['classroom']['file'];
               $.getJSON( 'data/classroom.json', Module.showClassrooms );
               return;
         }

         container = $("#classroom-options");
         container.empty();
         classrooms = Module.Schema['classrooms'];

         $.each( classroomRecords, function( classroomID, record ) {
               $("<a></a>", { "class" : "dropdown-item link-classroom", "href": "#" } )
                 .data('record', record )
                 .text( record['name'] )
                 .appendTo( container );
          });
 
     },                                 


    "showClassroom" : function( classroomRecord ) {

        container = $("#page-content");
        container.empty();
        $('<h2></h2>').html( classroomRecord['name'] ).appendTo(container);

        nav = $('<nav></nav>', { "class" : "nav nav-pills" } ).appendTo( container );
        $('<a></a>', { "class" : "nav-item nav-link active" }).html('<i class="fas fa-paperclip"></i> Submissions' ).appendTo(nav);
        $('<a></a>', { "class" : "nav-item nav-link" }).html('<i class="fas fa-scroll"></i> Assignments' ).appendTo(nav);
        $('<a></a>', { "class" : "nav-item nav-link" }).html('<i class="fas fa-user-friends"></i> Students' ).appendTo(nav);
        $('<a></a>', { "class" : "nav-item nav-link" }).html('<i class="fas fa-table"></i> Gradebook' ).appendTo(nav);

        classroomID = classroomRecord['id'];
        table = Module.createTable( 'received', { 'classroomID' : classroomID } );
        table.appendTo( container );

    },


    "icon" : function( icon ) {
      return $('<i></i>', { "class" : icon } );
     }, 


    "showLog" : function() {
        container = $("#page-content");
        container.empty();
        $('<h2></h2>').html('System Log').appendTo(container);
        table =  Module.entityTable('log').addClass('small').appendTo( container );
    },


    "createTable" : function( schemaEntity, tableOptions ) {

        schema = Module.Schema[ schemaEntity ] || false;
        if( ! schema ) return;

        table = $('<table></table>', {'class' : 'table', 'width' : '100%' } );

        config={
             "dom" : 'Bftip',
             "paging" : true,
             "pageLength" : 10,
             "ajax" : {
                  "method" : "POST",
                  "url" : "index.php?" + schemaEntity,
                  "data" : tableOptions || {},
                  "dataType" : "json"
               },
              "columns" : [],
              "buttons" : []
        }

        config = $.extend( config, schema['table'] || {} );

        if( config['altEditor'] === true ) {
          config['select'] = "os";
          config['buttons'].push(  {  "name" : "add",                           "text" : "Add "} );
          config['buttons'].push(  {  "name" : "edit",   "extend" : "selected", "text" : "Edit" } );
          config['buttons'].push(  {  "name" : "delete", "extend" : "selected", "text" : "Delete" } );
        }

        table.DataTable( config );
        return table;

    }

}


// ----------------------------


// After all is loaded...
$(document).ready(  function() { Module.onReady(); });

