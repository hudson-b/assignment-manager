var Parser = function( editor ) {
   
          // Reads the Codemirror content to get tokens
          // Structure:
          // 'lines' : [ each line of code ].
          // 'tagClass'  : {
          //   'tagName' : [ line#, line#, line#, /// ]
          //  } ...

          if( ! editor ) editor = Module['_code_editor_'];
         
          ignore = ['string','number'];

          analysis = { 'lines' : {} }

          editor.doc.eachLine( function( lineHandle ) {

                  lineNumber = editor.getLineNumber( lineHandle );
                  lineTokens = editor.getLineTokens( lineNumber );

                  analysis['lines'][ lineNumber ] = { "text" : lineHandle['text'], "tokens" : lineTokens };

                  $.each( lineTokens, function( tokenNumber, tokenObject ) {

                          tokenType = tokenObject['type'];
                          if( ! tokenType ) return;
                          if( tokenType in ignore ) return;

                          tokenID = tokenObject['string'] || 'unknown';

                          if( ! ( tokenType in analysis) ) analysis[ tokenType ] = {};
                          tokenTypeObject = analysis[ tokenType ];

                          if( ! (tokenID in tokenTypeObject) ) tokenTypeObject[ tokenID ] = [];
                          tokenIDObject = tokenTypeObject[ tokenID ];
                          tokenIDObject.push( lineNumber );
 
                  });
          });

          return analysis;

}



var Tester = function( sourceValue ) {

       tester = {
            'value' : sourceValue,
            'type' : typeof sourceValue,
            'always' : function() { return true; },
            'never' : function() { return false; }
        }


      switch( typeof sourceValue ) {

             case "object" :
             extend = {
                      "count" : Object.keys( sourceValue ).length,
                      "keys" : Object.keys( sourceValue ),
                      "list" : Object.keys( sourceValue ).join(","),

                      "hasMoreThan" : function( compareTo ) {
                        return this.count  > compareTo;
                      },
                      "hasFewerThan" : function( compareTo ) {
                        return this.count < compareTo;
                      },
                      "hasBetween" : function( compareFrom, compareTo ) {
                        return ( this.hasMoreThan( compareFrom ) && this.hasFewerThan( compareTo ) );
                      },
                      "contains" : function( compareTo ) {
                           if( $.isArray( compareTo ) ) {
                               original = this;
                               result = true;
                               $.each( compareTo, function( i, compareTo ) {
                                   if( ! ( compareTo in original.value ) ) {
                                      result = false;
                                      return false;
                                   }
                               });

                           } else {
                              result = ( compareTo in this.value ); // Check the keys only.
                           }
                           return result;
                      },
                      "notContains" : function( compareTo ) {
                        return ! ( (this.value).indexOf( compareTo ) > -1 );
                      }              
                }
                break;


             case "number" :
             case "bigint" :
             extend = {
                     "equals" :  function( compareTo ) {
                        return ( this.value == compareTo );
                     },
                     "equalsStrict" :  function( compareTo ) {
                        return ( this.value === compareTo );
                     },
                     "greaterThan" :  function( compareTo ) {
                        return ( this.value > compareTo );
                     },
                     "greaterThanOrEqualTo" : function( compareTo ) {
                        return ( this.value >= compareTo );
                     },
                     "lessThan" : function( compareTo ) {
                        return ( this.value < compareTo );
                     },
                     "lessThanOrEqualTo" :  function( compareTo ) {
                        return ( this.value <= compareTo );
                     },
                     "between" :  function( compareStart, compareEnd ) {
                        return ( this.greaterThanOrEqualTo( compareStart ) && this.lessThanOrEqualTo( compareEnd ) );
                     },
                     "betweenExclusive" :  function( compareStart, compareEnd ) {
                        return ( this.greaterThan( compareStart ) && this.lessThan( compareEnd ) );
                     }
                 }
                 break;



             case "string" : 
                 extend =  {
                     "isUpperCase" : function() {
                           return ( this.value == (this.value).toUpperCase() );
                     }, 
                     "isLowerCase" : function() {
                       return ( this.value == (this.value).toLowerCase() );
                     },
                     "isMixedCase" : function() {
                        return (this.isUpperCase() && this.isLowerCase());
                     },
                     "startsWith" : function ( compareTo ) {
                        return ( (this.value).substr(  (this.value).length - compareTo.length) === compareTo);
                     },
                     "endsWith" :  function( compareTo ) {
                        return ( (this.value).substr(0, compareTo.length) === compareTo);
                     },
                     "hasDigits" : function() {
                        return /\d/.test( this.value );
                     }

                 }
                 break;


            default : 
              // case "boolean" :
              // case "undefined":
                extend = {};
                break;

            }
 

   extend.format = function( message ) {
           tester = this;
           // If the value is an object, make a nice little list of all keys in the value
           if( tester.type == 'object' ) {
              tester['keys'] = Object.keys( tester.value ).join(",");
           }
           return message.replace(/\{\{|\}\}|\{(\w+)\}/g, function (m, n) {
                if (m == "{{") { return "{"; }
                if (m == "}}") { return "}"; }

                n=tester[n];
                if( $.isFunction(n) ) {
                     n = tester[n]();
                }
                return n;
            });
    }



    tester = $.extend( tester, extend );
    return tester;

}



var Grader = function ( submission, rubric ) {
                 
          grader = {
               "title" : rubric['title'] || rubric['file'],
               "student" : submission['student']['last_name'] + ', ' + submission['student']['first_name'],
               "assignment" : submission['assignment']['name'],
               "submission_number" : submission['number'] || '1',
               "submitted" : submission['submission']['time_submitted'],
               "score" : ( rubric['score'] || 0 ),
               "log" : []
          }


          // Walk all the sections of this rubric
          sections = [];

          rubricSections = (rubric['sections'] || [ {} ] ); // Always at least one 
          $.each( rubricSections, function( sectionNumber, sectionConfig ) {

               section = {
                     "number" :  sectionNumber,
                     "title" :  sectionConfig['title'] || 'Section ' + sectionNumber,
                     "description" :  sectionConfig['description'] || '',
                     "score" :  0
               }

               //  Merge down all tests into a single collection
               sectionTestGroups = [];

               // Add any tests explicitly defined at this section level
               if( 'tests' in sectionConfig ) sectionTestGroups.push(  { 'tests' : sectionConfig['tests'] } );

               // Add in any included configurations for this section
               $.each(  ( sectionConfig['included'] || [] ), function( includeNumber, includeConfig ) {
                   sectionTestGroups.push( includeConfig );
               });


              // Walk each test group
              tests=[];
              $.each( sectionTestGroups, function( testGroupNumber, testGroupConfig ) {
     
                  if( ! testGroupConfig ) return;

                   // Walk each test and score it
                  $.each( testGroupConfig['tests'] || [] , function( testNumber, testConfig ) {
                             
                             testNumber = sectionNumber + '.' + testGroupNumber + '.' + testNumber;

                             test = {
                                 "number" : testNumber,
                                 "title" : testConfig['title'] || 'Test #' + testNumber,
                                 "description" : testConfig['description'] || '',
                                 "score" : 0,
                                 "results" : [] 
                             }

                             // Normalize a bit.  Replace optional iteration methods
                             $.each( ['all', 'each' ], function( i, key ) {
                                 if( key in testConfig ) {
                                    testConfig['source'] = testConfig[key];
                                    testConfig[key] = true;
                                 }
                             });
                          
                             // What shall we test upon?
                             testItem = testConfig['source'] ||          // at the test level
                                        testGroupConfig['source'] ||     // .. or at the test group level
                                        sectionConfig['source'] ||       // .. or at the section level
                                        "__";                         // Give up and go home


                             // We must have something to test..
                             if( testItem == false ) {
                                 grader['log'].push( 'Test : ' + testNumber + ' : Nothing to test' );
                                 return;

                             } else if( ! testItem in rubric['analysis'] ) {
                                 grader['log'].push( 'Test : ' + testNumber + ' : No analysis data for ' + testItem );
                                 return;
                             }

                             if( testConfig['each'] === true ) {
                                   testData = Object.keys( analysis[ testItem ] );

                             } else { 
                                   testData = [ Object.keys( analysis[testItem] ) ];  // One element, with all items (grin)
                             }

                             // Walk all the things that need scoring
                             scored=[];
                             $.each( testData, function( testItemKey, testItemValue ) {
    
                                       tester = Tester( testItemValue );
                                   
                                       // Walk the scoring collection and score this thing
                                       $.each( ( testConfig['scoring'] || [] ), function( scoreNumber, scoreConfig ) {

                                              if( ! scoreConfig ) return;

                                              // Just a string?  Convert to an object with a message property
                                              if ( typeof scoreConfig === 'string' )  scoreConfig = { 'always' : true, 'message' : scoreConfig }

                                              scoreNumber = testNumber + '.' + scoreNumber;
                                              scoreTitle = ( scoreConfig['title'] || 'Score #' + scoreNumber );
                                              scoreMatch = ( scoreConfig['match'] || testConfig['match'] || 'first' );
 
                                              // How to compare?  Check the first key.
                                              compareMethod = Object.keys( scoreConfig )[0]; 
                                              compareValue =  Object.values( scoreConfig )[0];
 
                                              // If the first key is message, force always
                                              if( compareMethod == "message" )  {
                                                  compareMethod = "always";
                                                  compareValue = true;
                                              }

                                              // Can we even make this comparison?
                                              if ( ! ( compareMethod in tester ) ) {
                                                    grader['log'].push( testNumber + ' : ' + scoreTitle + ' : `' + compareMethod + '` is not a valid TestItem method for ' . tester.type );
                                                    return; // continue the scoring loop 
                                              }
                                                  
                                              // Manufacture the pass settings
                                              testPass =  scoreConfig['pass'] || {  'score' : scoreConfig['score'], 'message' : scoreConfig['message'] , 'icon' : scoreConfig['icon']};

                                              // .. and (possibly) the fail settings
                                              testFail =  scoreConfig['fail'] || false;
  
                                              // Do the test
                                              compareResult = tester[ compareMethod ]( compareValue ) || false;

                                              // console.log( 'Compared',  tester.value, 'to', compareValue, 'using', compareMethod, 'returned', compareResult );

                                              // Match the result to what was expected and choose
                                              testResult = ( compareResult ) ? testPass : testFail;

                                              // If the test failed, and we have no explicit fail settings, move along
                                              if ( testResult ) {

                                                 // Expand the message text
                                                 testResult = (  JSON.parse( JSON.stringify( testResult) ) ); // Make a copy
                                                 testResult['title'] = tester.format( testResult['message'] || '' ); 
                                              
                                                 // Add the result to the scored collection
                                                 scored.push( testResult );

                                                 if( 'score' in testResult ) {
                                                        section['score'] = ( section['score'] || 0 ) + ( testResult['score'] || 0 );
                                                        grader['score'] = ( grader['score'] || 0 ) + ( testResult['score'] || 0 );
                                                 }

                                                 // Continue scoring?
                                                 continueScoring = ( scoreConfig['match'] || testConfig['match'] || '' ) == 'all';

                                                 if ( continueScoring === false ) return false; // Stop the loop 
                                              }

                                                
                                         });

                                    }); // Scoring

                                 // Store the result of the scoring
                                 test['results'] = scored;
                                 tests.push( test );

                           }); // Test Values
           
            }); // Test Groups                   
          section['results'] = tests;
          sections.push( section );

          }); // Section

    grader['results'] = sections;

    container.html('');
    detail = $('<pre></pre>').text( JSON.stringify( grader, null, 4 ) ).appendTo( container );
    container.show();


    return grader;

}







