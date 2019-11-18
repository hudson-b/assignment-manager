// Include any custom functions that might be needed by a rubric
var Tests = {

   "count" : function( collection ) {
         return collection.length;

   },

   "containsNumber" : function( value ) {
     return /\d/.test( value g);
   },
   "containsLetter" : function( value ) {
     return true;
   },

   "variableQuality" : function( value ) {
       q = value.length;
       if( value === value.toUpperCase() ) q = q / 2;
       if( value === value.toLowerCase() ) q = q / 2;
       return q;
   }


}

