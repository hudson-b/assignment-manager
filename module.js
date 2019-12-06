// -------------------------------------
var Module = {

    "onReady" : function() {


            // Custom buttons
            $.fn.dataTable.ext.buttons.refresh = {

                "text": '<i class="fas fa-redo-alt"></i>&nbsp;Refresh',
                "className" : "btn btn-sm btn-refresh btn-outline-primary",

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
  "signOf" : function ( number ) {
    if (isNaN( number ) )  return '';
    switch ( Math.sign( number ) ) {
     case -1 : return '-';
     case 1 : return '+';
     default : return '';
    }
  },


  "dialog" : function( config ) {
     config['className'] = 'bootbox-dialog';
     Module['_dialog_'] = bootbox.dialog( config )
     return Module['_dialog_'];
  },

  "alert" : function( config ) {
     Module['_dialog_'] = bootbox.alert( config )
     return Module['_dialog_'];
  },

  "confirm" : function( config ) {

     if( typeof config == "string" ) config = { "message" : config, "buttons" : "ok" , "callback" : function() {} }

     if( (config['buttons'] || '' ) in Module.schemas.buttons ) config['buttons'] = Module.schemas.buttons[ config['buttons'] ];

     Module['_dialog_'] = bootbox.confirm( config )
     return Module['_dialog_'];

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
                       },
              'classroom' : classroomRecord
           }
           Module.showSchema( 'submissions', customConfig );

       });
     
   },
         

   "showAssignments" : function( classroomRecord ) {
     // Go get the assignments
   },



   "showSubmission" : function( record ) {

                  
                   title = $("<div></div>", { "class" : "submission-title" } );
                   $('<h2></h2>' ).html( record['student']['last_name'] + ', ' +  record['student']['first_name'] ).appendTo( title );
                   $('<h5></h5>' ).html( record['assignment']['name'] ).appendTo( title );


                   container = $("<div></div>", { "class" : "submission-container" } );

                   code = record['submission']['files'][0]['content'];
                   containerCode = $("<div></div>", { "class" : "submission submission-code" } ).appendTo( container );

                   //textarea = $("<textarea></textarea>", { "class" : "submission-code-raw" }).text( code ).appendTo( ".submission-code" );
                   //textarea.appendTo( containerCode );
                   containerAnalysis = $("<div></div>", { "class" : "submission submission-analysis" } ).appendTo( container );
                 

                   // Show the window
                   Module.dialog({
                       "backdrop" : true,
                       "title" : title,
                       "size" : "large",
                       "message" :  container,
                       "record" : record,
                       "buttons" : {

                            "code" : {
                               "label" : "Code",
                               "className" : "btn btn-outline-primary",
                               "callback" : function() { 
                                     $(".submission").hide();
                                     $(".submission-code").show();
                                     return false;
                               }
                             },

                            "analysis" : {
                               "label" : "Analysis",
                               "className" : "btn btn-outline-info",
                               "callback" : function() { 
                                     $(".submission").hide();

                                     container = $(".submission-analysis");
                                     container.html('');
                                     analysis = Parser();
                                     detail = $('<pre></pre>').text( JSON.stringify( analysis, null, 4 ) ).appendTo( container );
                                     container.show();

                                     return false;
                               }
                             },
                            "grade" : {
                               "label" : "Grade",
                               "className" : "btn btn-outline-success",
                               "callback" : function() { 
                                     $(".submission").hide();
                                     container = $(".submission-analysis");
                                     container.html('');
                                     Module.gradeSubmission( record );
                                     return false;
                                }
                             }

                        }

                   }).on('shown.bs.modal', function(e) {

                           console.log( record );

                           code = record['submission']['files'][0]['content'];

                           Module['_code_editor_'] = CodeMirror(
                                 $(".submission-code")[0],
                                 { "value" : code,
                                    "lineNumbers" : true,
                                   "mode" : "python",
                                   "addModeClass" : true,
                                   "viewportMargin" : Infinity
                           });
                           Module['_code_editor_'].refresh();

            
                   });

   },

 

    
  "gradeSubmission" : function( submission, rubric ) {

          if( ! rubric ) {

            Module.fetchData( { "rubrics" : true, "includes" : true } )
                    .then(  function(rubrics) {

                        container = $(".submission-analysis");
                        container.html('');

                        $.each( rubrics['data'], function(index, rubric) {

                               if( ! ('title' in rubric) ) return;

                                 button=$('<button></button>', { "class" : "btn btn-outline-primary btn-block" })
                                  .data('submission', submission )
                                  .data('rubric', rubric )
                                  .html( rubric['title'] )
                                   .on('click', function() { 
                                         submission = $(this).data('submission');
                                         rubric = $(this).data('rubric');
                                         Module.gradeSubmission( submission, rubric );
                                 });

                                button.appendTo( container );

                         });
                         container.show();
                        
                  });
             return;

          }


          // If there's a mode option in the rubric, use that to reset the editor
          if( 'mode' in rubric ) {
              codeEditor = Module['_code_editor_'];
              mode = ( rubric['mode']  || { 'name' : 'python' } );
              codeEditor.setOption("mode",  mode );
              codeEditor.refresh();
          }

          // Go get the analysis of the current code window and stored it in the rubric
          rubric['analysis'] = Parser( codeEditor, 'api' );

          // Grade that sucker
          graded = Grader( submission , rubric );


          var formatResults = function( gradeObject, depth ) {

               if( ! depth ) depth = 0;

               var div = $('<div></div>', { "class" : "grader-container grader-depth-" + depth } );

               var title = $('<span></span>' );

               var badgeScore = gradeObject['score'] || false;
               if ( ! ( badgeScore === false ) ) {

                     var badge = $('<i></i>');
                     var badgeClass = ( badgeScore < 0 ? "danger": "success" ) ;
                     if( ( gradeObject['type'] || '' ) == 'score' ) {
                        if( badgeScore > 0 ) badgeScore = '+' + badgeScore;
                     }

                     badge.html( badgeScore );
                     badge.addClass("float-right badge badge-" + badgeClass);
                     badge.appendTo( title );
               }

               title.append( gradeObject['title'] || gradeObject['message'] );
               title.appendTo( div );
 
               $.each( gradeObject['results'] || [], function( i,item ) {
                   if( ! item ) return;
                   div.append(  formatResults( item, depth+1 ) );
               });

               return div; 
             
          }

          container = $(".submission-analysis");
          container.html('');

          formatted = formatResults( graded, 0 );

          // console.log( formatted );        
          container.append( formatted );

          container.append( "<hr>" );
          buttonSave = $("<span></span>", { "class" : "btn btn-success btn-block" } )
            .data( 'graded', graded )
            .text("Save To Gradebook")
            .on('click', function() {
                   
                 id = $(this).data('id');
                 graded = $(this).data('graded');

                 Module.postData( 'graded=true', graded )
                    .then( function(d) {
                           Module['_dialog_'].modal('hide');                
                           $(".btn-refresh").click();
                     });

            });

          container.append( buttonSave );
          

   },




  "editRubric" : function( record ) {

     if( ! record ) record = { "title" : "New Rubric", "content" : "" }

     editor=$("<textarea></textarea>")
       .addClass("rubric-editor hidden")
       .data( record  )
       .text( record['content'] || "" );
      
      Module.dialog({
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
                           Module.confirm( d['error'] || d['message'] );
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
        //console.log( config );
  },





   "schemas" : {
     "buttons" : {

        "ok" : {
            confirm: {
                label: 'OK',
                className: 'btn-success'
            },
        },

        "yesno" : {
            confirm: {
                label: 'Yes',
                className: 'btn-success'
            },
            cancel: {
                label: 'No',
                className: 'btn-danger'
            }    
        }


     },


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

     "icons" : {
          "pass" : $('<i></i>', { "class" : "fas fa-check-circle",   "style" : "color:green" }),
          "fail" : $('<i></i>', { "class" : "fas fa-times-circle",   "style" : "color:red" }),
          "warn" : $('<i></i>', { "class" : "fas fa-flag-checkered", "style" : "color:orange" }),
          "info" : $('<i></i>', { "class" : "fas fa-info-circle",    "style" : "color:tan" })
      },


 
     "submissions" : {
             "icon" : "fab fa-leanpub",
             "title" : "Student Submissions",
             "dom" : 'Bftip',
             "select" : "single",
             "responsive" : true,
             "order" : [ [1, "desc"] ],
             "buttons" : [
                        'refresh',
                        {
                          "text" : '<i class="fa fa-book"></i>&nbsp;Gradebook', 
                          "className" : "btn btn-sm btn-outline-primary",
                          "action" : function( button, datatable, buttonNode, buttonConfig) {

                               settings = datatable.settings().init();
                               classroomID = settings['classroom']['id'];
                               classroomName = settings['classroom']['name'];

                               // Walk all visible rows
                               gradebook = {};
                               data = datatable.rows({ "filter" : 'applied' } ).data();
                               $.each( data, function(i, record  ) {

                                      if( ! ( 'graded' in record ) ) return;

                                      studentID = record['student']['id'];
                                      if ( ! ( studentID in gradebook ) ) {
                                        gradebook[ studentID ] = { 'classroom' : record['classroom']['name'], 'email' : record['student']['email'],'name' : record['student']['last_name']+', '+record['student']['first_name'] }
                                      }

                                      gradebook[studentID][ record['assignment']['name'] ] = record['graded']['score'];

                               });
                                                                                                      
                               now = moment().format('YYYY-MM-DD-HH-mm');
                               download( JSON.stringify( gradebook , null, 2 ), "gradebook_" + classroomName + "_" + now + ".json", "application/json" );

                               /*
                               Module.fetchData( { "gradebook" : classroomID  } )
                                .then( function(d) {
                                   now = moment().format('YYYY-MM-DD-HH-mm');
                                   download( JSON.stringify( d , null, 2 ), "gradebook_" + classroomName + "_" + now + ".json", "application/json" );
                                 });
                               */

                           }
                        },
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
               { "data" : "assignment.name",  "title" : "Assignment" , "class" : "filter", "orderable" : false },
               { "data" : "submission.number",  "title" : "Submission<br>Number"},
               { "data" : "student", 
                 "title" : "Student", 
                  "render" : function(d) { return d.last_name + ', ' + d.first_name; } 
               },
               { "data" : "submission.status",  "title" : "Submission<br>Status" },
               { "data" : null, 
                 "title" : "Graded",
                 "render" : function( d,t,r ) {
                      graded = ( r['graded'] || false);
                      return (graded) ? moment(graded['graded_date']).format('YYYY-MM-DD hh:mma') : ''; 
                  },
               },
               { "data" : null, 
                 "title" : "Score",
                 "render" : function( d,t,r ) {
                      graded = ( r['graded'] || {} );
                      return  graded['score'] || '';
                  },
               }
              ],
             "initComplete" : function () {
                this.api().columns(".filter" ).every( function () {
                     Module['schemas']['_filter_']( this );
                });
             }

       },


      "rubrics" : {

             "icon" : "fas fa-scroll",
             "title" : "Rubrics",
             "dom" : 'Bftip',
             "ajax" : {
                 "url" : "index.php?rubrics=true",
                 "method" : "OPTIONS",
                 "dataType" : "json"
              },
             "order" : [ [0, "desc"] ],
             "paging" : true,
             "columns" : [
                  { "data" : "id", "title" : "Rubric ID", "render" : function(d,t,r) { return d || r['file'];}  },
                  { "data" : "title", "title" : "Title", "render" : function(d,t,r) { return d || '(include only)' }  },
                  { "data" : "file_info.modified", "title" : "Last Modified",  }
              ],
             "buttons" : [
                         'refresh',
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
                                   record = datatable.rows({ "selected" : true } ).data()[0];

                                   rubricID = record['id'];
                                   rubricName = record['title'] || rubricID;

                                   Module.confirm({
                                         "title" : "Remove Rubric",
                                         "message" : "Are you sure you wish to remove the rubric " + rubricName + '?',
                                         "buttons" : "yesno",
                                         "callback" : function(result) {
                                              if( ! result ) return;
                                              Module.postData( "delete", { "entity" : "rubrics", "id" : rubricID })
                                              .then( function(m) {  Module.confirm( m['message'] );  });
                                              $(".btn-refresh").click();
                                             
                                          }

                                   });
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
               'refresh'
              ],
             "order" : [ [0, "desc"] ],
             "paging" : true,
             "columns" : [
                  { "data" : "datetime", "title" : "Date" },
                  { "data" : "remote_addr", "title" : "IP" },
                  { "data" : "channel", "title" : "Channel", "class" : "filter" },
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

