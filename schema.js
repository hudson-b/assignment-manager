Module['Schema'] =  {


      "bySubmission" : {
          "table" : {
             "select" : "single",
             "order" : [ [1, "desc"] ],
             "buttons" : [
                { "extend" : "selected", "text" : "View"  }
             ],
             "columns" : [
               { "data" : "submission.id",  "title" : "ID" },
               { "data" : "submission.time_submitted",  "title" : "Submitted", "render" : function(d) { return moment(d).format('YYYY-MM-DD hh:mm'); }  },
               { "data" : "assignment.name",  "title" : "Assignment"},
               { "data" : "student", "title" : "Student", "render" : function(d) { return d.last_name + ', ' + d.first_name; } },
               { "data" : "submission.status",  "title" : "Status" }
              ]
           }
       },

      "byStudent" : {
          "table" : {
             "select" : "single",
             "order" : [ [0, "asc"] ],
             "buttons" : [
                { "extend" : "selected", "text" : "Assign Rubric"  }
             ],
             "columns" : [
               { "data" : "id",  "title" : "ID" },
               { "data" : "name",  "title" : "Name" },
               { "data" : "type",  "title" : "Type"}
              ]
           }
       },


      "byAssignment" : {
          "table" : {
             "select" : "single",
             "order" : [ [0, "asc"] ],
             "buttons" : [
                { "extend" : "selected", "text" : "Assign Rubric"  }
             ],
             "columns" : [
               { "data" : "id",  "title" : "ID" },
               { "data" : "name",  "title" : "Name" },
               { "data" : "type",  "title" : "Type"}
              ]
           }
       },



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
      },



      "log" : {
          "entity" : "log",
          "title" : "Log",
          "file"  : "data/master.log",
          "table" : {
             "order" : [ [0, "desc"] ],
             "paging" : true,
             "columns" : [
                  { "data" : "datetime", "title" : "Date" },
                  { "data" : "remote_addr", "title" : "IP" },
                  { "data" : "channel", "title" : "Channel", "visible" : false },
                  { "data" : "level", "title" : "Level" , "visible" : false},
                  { "data" : "level_name", "title" : "Level Name" },
                  { "data" : "message", "title" : "Message" },
                  { "data" : "context", "title" : "Context", "visible" : false },
                  { "data" : "extra", "title" : "Extra", "visible" : false }
              ]
            }
       }   

}

