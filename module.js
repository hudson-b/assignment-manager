// -------------------------------------
var Module = {

    "onReady" : function() {


            $.fn.dataTable.ext.buttons.reload = {

                "text": '<i class="fas fa-redo-alt"></i>&nbsp;Reload',
                "className" : "btn btn-sm btn-outline-primary",

                "action": function ( e, dt, node, config ) {
                     dt.ajax.reload( function() {
                       $(node).pulsate({ repeat : false, reach: 40 });
                    });

                }
            };


             // Display the project version number
             $("#version").load("VERSION");

             // Handle the main navbar links
             $("#link-log").click( function() { Module.showSchema('log'); }  );
             $("#link-rubrics").click( function() { Module.showSchema('rubrics'); }  );

             // Fetch the data and populate the selector
             Module.fetchData( { "classrooms" : true } )
                .then( function(d) {
                     Module.showClassrooms(d);
                });

 
   },


  "clear" : function() {
        container = $("#page-content");
        container.empty();
  },


  "fetchData" : function( filterObject ) {

          filterParms = $.param( filterObject );
          return $.ajax({
                    "url" : "index.php?" + filterParms,
                    "method" : "OPTIONS",
                    "dataType" : "json"
          });
   },

  "postData" : function( destination, data ) {

          return $.ajax({
                    "url" : "index.php?" + destination,
                    "method" : "POST",
                    "dataType" : "json",
                    "data" : { 'data' : data }
          });
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
           classroomRecord=$(this).data('classroom');
           customConfig = {
              'title' : classroomRecord['name'],
              'ajax' : function( data, callback, settings ) {
                          Module.fetchData( {'classroom' : classroomRecord['id'] } )
                         .then( function(d) {   callback( {'data' : d }); } );
                       }
           }
           Module.showSchema( 'submissions', customConfig );
       });
     
   },
         


  "showClassroom" : function( classroom ) {

        /*
        // Build unique collections from all records.
        data = { 'all' : records };

        $.each( records,  function( recordIndex, record ) {

             $.each( record, function( subKey, subObject ) {
                if( ! $.isPlainObject( subObject ) ) return;
                if( ! ('id' in subObject ) ) return;
                if( ! ( subKey in data ) ) data[subKey] = {};
                subID = subObject['id'];
                data[ subKey ] [ subID ] = subObject;
             });

        });

        // Flatten them (turn from associative to indexed)
        $.each( data, function( subKey, collection ) {
            data[subKey] = Object.values( collection );
        });
        classroom = data['classroom'][0] || {};

        Module.clear();

        // Title.
        title = $('<h5></h5>', { "class" : "text-center" } ).appendTo(container);
        title.append( classroom['name'] || '' );
 
        nav = $('<ul></ul>', { "id" : "classroom-nav", "class" : "nav nav-tabs" } ).appendTo( container );
        content = $('<div></div>', { "class" : "tab-content pad-top" } ).appendTo( container );

        // Show the tabs
         items = { 
            'submissions' : { 'caption' : 'Submissions', 'icon' : 'fas fa-paperclip', 'table' : 'submissions', 'filter' : classroom['id'] },
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

         });

        // Set up handlers
        $('a.classroom-item').on('click', function(e) {
                e.preventDefault();
                $(this).tab('show');
        });

        // Click the first one
        $('a.classroom-item').first().click();

        */


    },


   "showSubmission" : function( record ) {

                   fileCreated = record['file_created'];

                   title = '<h2>' +
                                record['student']['last_name'] + ', ' +  record['student']['first_name'] + '&nbsp;&nbsp;' + 
                               '<small><small>' + record['assignment']['name'] + '</small></small>' +
                           '<h2>' + 
                           '<h5>Received :  ' + fileCreated + '</h5>';


                   container = $("<div></div>", { "class" : "container" } );
                   row = $("<div></div>", { "class" : "row" } ).appendTo( container );

                   colA = $("<div></div>", { "class" : "col-sm-7 fixed-height-400 student-code-container" } ).appendTo( row );
                   content = record['submission']['files'][0]['content'];
                   content = $("<textarea></textarea>", { "id" : "student-code", "class" : "student-code" } ).val(content).appendTo( colA );
                   
                   colB = $("<pre></pre>", { "class" : "col      fixed-height-400 student-code-analysis" } ).appendTo( row );

                   // Rubric Picker
                   

                   // Show the grader window
                   bootbox.dialog({
                       "title" : title,
                       "size" : "extra-large",
                       "message" :  container,
                       "buttons" : {
                            "history" : { 
                               "label" : "Other Submissions", 
                               "className" : "btn btn-info",
                               "callback" : function() { return 1; }
                             },

                            "analyze" : { 
                               "label" : "Analyze", 
                               "className" : "btn btn-primary",
                               "callback" : function() {
                                   // get all classes :)  Already tokenized,
                                   analysis={};
                                   //tags=$('[class^="cm-m"]');
                                   tags=$('.cm-m-python');
                                   tags.each( function() {
                                      tag=$(this);

                                      lineNumber = tag.parent().parent().parent().find(".CodeMirror-linenumber").html();
                                      console.log( lineNumber );

                                      tagClass = ( tag.attr("class").split(" ")[1] || '' ).replace("cm-", "" );
                                      if( tagClass == '' ) return;

                                      tagValue = tag.html();

                                      if( ! ( tagClass in analysis ) ) analysis[ tagClass ] = {};

                                      tagObject =(  analysis[ tagClass ][ tagValue ] || { 'lines' : [] } );
                                      tagObject['lines'].push(lineNumber);
                         
                                      analysis[ tagClass ][ tagValue ] = tagObject;
 

                                   });
                                   $(".student-code-analysis").html( JSON.stringify( analysis, null, 4 ) );
                                   return false;
                                   
                                }
                             }

                        }

                   }).on('shown.bs.modal', function(e) {

                      Module['_code_editor_'] = CodeMirror.fromTextArea( $("textarea.student-code")[0],
                        { "lineNumbers" : true,
                          "mode" : "python" ,
                          "addModeClass" : true
                        });

                   });

   },
 
   "examineSubmission" : function( record ) {
      $.ajax({
           "url" : "?grade",
           "method" : "POST",
           "format" : "json",
           "data" : record

      }).then( function( d ) {
          console.log( d );
          info = JSON.stringify( d, null, 2 );
          $(".student-code-summary").html( info );
      });

   },



  "editRubric" : function( record ) {

     if( ! record ) record = { "title" : "New Rubric", "content" : "" }

     editor=$("<textarea></textarea>")
       .addClass("rubric-editor hidden")
       .data( record  )
       .text( record['content'] || "" );
      
      bootbox.dialog({
           "title" : record['title'],
           "size" : "extra-large",
           "message" :  editor,
           "buttons" : {
                "close" : { 
                   "label" : "Close", 
                   "className" : "btn btn-info"
                 },
                "save" : { 
                   "label" : "Save", 
                   "className" : "btn btn-success",
                   "callback" : function() { 
                       content = Module['_rubric_editor_'].getValue();
                       Module.postData( 'rubric', content )
                       .then( function(d) {
                                window.alert( d['error'] || d['message'] || 'Done' );
                       });
                       return false;
                    }
                 }
            }
        }).on('shown.bs.modal', function(e) {

             Module['_rubric_editor_'] = CodeMirror.fromTextArea( $("textarea.rubric-editor")[0],
              { "lineNumbers" : true,
                    "mode" : "application/ld+json" ,
                    "json" : true,
                   "addModeClass" : true,
                   "value" : record['content']
              });
       });

  },

  "saveRubric" : function ( code ) {
  },






  "showSchema" : function( schemaKey, configCustom ) {

        config = Module['getSchema']( schemaKey );
        if( ! config ) return;

        config = $.extend( config, configCustom || {} );

        container = $("#page-content");
        container.empty();

        title = config['title'] || '';
        if( 'icon' in config ) title = '<i class="' + config['icon'] + '"></i>&nbsp;' + title;

        $('<h4></h4>').html( title ).appendTo(container);
        table =  $('<table></table>', { "class" : "table small responsive", "width" : "100%" } ).wrap('<div></div>').appendTo( container );
        table.DataTable( config );
        console.log( config );
  },


  "getSchema" : function( schemaKey ) {

        schema = Module['schemas'][ schemaKey ] || false;
        if( ! schema ) return;
         
        config={
                 "dom" : 'Bftip',
                 "paging" : true,
                 "pageLength" : 10,
                 "select" : "os",
                 "columns" : []
         }
        config = $.extend( config, schema || {} );


        if( 'buttons' in config ) {
              config['buttons'] = {  
                 "dom" : {  "button" : {  "className" : "btn btn-sm" }  },
                 "buttons" : config['buttons']
              }
        }

        return config;

   },



   "buttons" : {

      "refresh" : {
                       "className" : "btn btn-sm btn-outline-primary",
                       "text": 'Refresh',
                       "action": function ( e, dt, node, config ) {
                            dt.ajax.reload();
                        }
      }
   },


   "schemas" : {

     "_filter_" : function(column,options) {

           var select = $('<select></select>')
               .appendTo( $(column.header()).empty() )
               .on( 'change', function () {
                   var val = $.fn.dataTable.util.escapeRegex(   $(this).val()   );
                   val = ( val ) ? '^'+val+'$' : ''
                   column.search( val , true, false ).draw();
               });

           if( ! options ) { options = column.data().unique().sort(); }

           select.append( '<option value="" SELECTED">--All--</option>' );
           options.each( function ( d, j ) {
                 select.append( '<option value="'+d+'">'+d+'</option>' )
           });

     },



     "submissions" : {
             "icon" : "fab fa-leanpub",
             "title" : "Student Submissions",
             "dom" : 'Bftip',
             "select" : "single",
             "responsive" : true,
             "order" : [ [1, "desc"] ],
             "buttons" : [
                        'reload',
                        {
                          "extend" : "selected", 
                          "text" : '<i class="fa fa-user"></i>&nbsp;View Submission', 
                          "className" : "btn btn-sm btn-outline-info",
                           "action" : function( button, datatable, buttonNode, buttonConfig) {
                               record = datatable.rows({ "selected" : true } ).data()[0];
                               Module.showSubmission( record );
                             }
                         }
              ],
             "columns" : [

               { "data" : "submission.id",  "title" : "ID" , "visible" : false },
               { "data" : "submission", 
                 "title" : "Received", 
                 "render" : function(o) { 
                     dd = o['time_received'] || o['time_submitted'];
                     return moment(dd).format('YYYY-MM-DD hh:mma'); } 
               },
               { "data" : "assignment.name",  "title" : "Assignment" },
               { "data" : "submission.number",  "title" : "Submission<br>Number"},
               { "data" : "student", 
                 "title" : "Student", 
                  "render" : function(d) { return d.last_name + ', ' + d.first_name; } 
               },
               { "data" : "submission.status",  "title" : "Submission<br>Status" },
               { "data" : "grader.status",  "title" : "Grade<br>Status" },
               { "data" : "grader.grade",  "title" : "Grade" }
              ]
       },


      "rubrics" : {

             "icon" : "fas fa-scroll",
             "title" : "Rubrics",
             "dom" : 'Bftip',
             "ajax" : {
                 "url" : "index.php?rubrics",
                 "method" : "OPTIONS",
                 "dataType" : "json"
              },
             "order" : [ [0, "desc"] ],
             "paging" : true,
             "columns" : [
                  { "data" : "title", "title" : "Title" },
                  { "data" : "modified", "title" : "Last Modified" }
              ],
             "buttons" : [
                         'reload',
                          {
                             "text" : "Add" , 
                             "className" : "btn btn-sm btn-outline-success",
                             "action" : function( button, datatable, buttonNode, buttonConfig) {
                               Module.editRubric();
                              }
                          },
                          {
                             "extend" : "selected", 
                             "text" : "Edit" , 
                             "className" : "btn btn-sm btn-outline-primary",
                             "action" : function( button, datatable, buttonNode, buttonConfig) {
                               record = datatable.rows({ "selected" : true } ).data()[0];
                               Module.editRubric( record );
                              }
                          },
                          {
                             "extend" : "selected", 
                             "text" : "Delete" , 
                             "className" : "btn btn-sm btn-outline-danger",
                             "action" : function( button, datatable, buttonNode, buttonConfig) {
                                   
                              }
                          },
 
             ]

     },


      "log" : {
             "icon" : "fas fa-list",
             "title" : "System Log",
             "dom" :  "Bftip",
             "ajax" : {
                 "url" : "index.php?log",
                 "method" : "OPTIONS",
                 "dataType" : "json"
              },
             "buttons" : [
               'reload'
              ],
             "order" : [ [0, "desc"] ],
             "paging" : true,
             "columns" : [
                  { "data" : "datetime", "title" : "Date" },
                  { "data" : "remote_addr", "title" : "IP" },
                  { "data" : "channel", "title" : "Channel", "visible" : false },
                  { "data" : "level", "title" : "Level" , "visible" : false},
                  { "data" : "level_name", "class" : "filter", "orderable" : false, "title" : "Level Name" },
                  { "data" : "message", "title" : "Message" },
                  { "data" : "context", "title" : "Context", "visible" : false },
                  { "data" : "extra", "title" : "Extra", "visible" : false }
             ],
            "initComplete" : function () {
               this.api().columns(".filter" ).every( function () {
                    Module['schemas']['_filter_']( this );
               });
            }
              
         } ,



      "classroom" : {
         "entity" : "classroom",
         "title" : "Classrooms",
         "file"  : "data/classroom.json",
         "table" : {
             "altEditor" : true,
             "order" : [ [1, "asc"] ],
             "columns" : [
                { "data" :"id",               "title" : "ID" },
                { "data" :"name",             "title" : "Name" },
                { "data" : "webhook_secret" , "title" : "Secret" }
             ]
         }
       },

      "assignment" : {
          "entity" : "assignment",
          "title" : "Assignments",
          "file"  : "data/assignment.json",
          "table" : {
             "altEditor" : true,
             "order" : [ [0, "desc"] ],
             "columns" : [
                  { "data" : "id",    "title" : "ID" },
                  { "data" : "name",  "title" : "Name" },
                  { "data" : "type",  "title" : "Type" }
             ]
           }
      },

      "submission" : {
          "entity" : "submission",
          "title" : "Submissions",
          "file"  : "data/submission.json",
          "table" : {
             "altEditor" : true,
             "order" : [ [2, "DESC"] ],
             "columns" : [
               { "data" : "id" ,  "title" : "ID" },
               { "data" : "status",  "title" : "Status" },
               { "data" : "time_submitted",  "title" : "Submitted" },
               { "data" : "time_created",  "title" : "Created" },
               { "data" : "teacher_url",  "title" : "Teacher Link" },
               { "data" : "student_url",  "title" : "Student Link" },
               { "data" : "student_id",  "title" : "Student ID" },
               { "data" : "assignment_id",  "title" : "Assignment ID" }
            ]
          }

      },

      "student" : {
          "entity" : "student",
          "title" : "Students",
          "file"  : "data/student.json",
          "table" : {
             "order" : [ [2, "asc"], [1, "asc"]  ],
             "paging" : true,
             "columns" : [
              { "data" :  "id",  "title" : "ID" },
              { "data" : "first_name",  "title" : "First Name" },
              { "data" : "last_name", "title" : "Last Name" },
              { "data" : "email",  "title" : "Email" }
             ]
            }
      }

  }

}


// After all is loaded...
$(document).ready(  function() { Module.onReady(); });

