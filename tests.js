// The Tester class lets you bind an item and perform tests on it.
function ItemTester( value )  {

  if( value === null ) return {};
  console.log('new ItemTester : ', value, typeof value )
  switch( typeof value ) {


     case "object" :
        base = {
              "count" : Object.keys( value ).length,

              "keys" : Object.keys(value),

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
        base = {
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
         base =  {
             // String stuff
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
        base = {};
        break;

    }

   base.value = value;
   base.type = typeof value;

   base.always = function() { return true; }
   base.never = function() { return false; }

   base.format = function( message ) {

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


  return base;

}





