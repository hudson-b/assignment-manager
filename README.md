# assignment-manager
Integration / Manager for Repl.it -> Style grading 

<h3>Project Goals</h3>
This project provides a flexible mechanism for grading student code style.  For example, checking the quality of variable names (all lower/upper case), or
verifying that comments are present, or even checking for the usage of a particular technique such a in place modification or a special keyword usage.  The
<i>rubric</i> structure defines a collection of tests that examine the code in any number of ways.  You can store as many rubrics as needed.  
Student code is submitted to the project from the site <i>Repl.it</i> using their <a href="">webhook</a> event.  

<h3>Presentation Video</h3>
https://www.youtube.com/playlist?list=PLxin6nRt8bolA9lgspeJZkxSX0zITcS-D


<h3>Demo Site</h3>
<hr>
<dl>
 <dt>URL</dt><dd>https://faculty.lynchburg.edu/hudson_b/demo/assignment-manager</dd>
 <dt>User</dt><dd>guest</dd>
 <dt>Password</dt><dd>demo</dd>
</dl>


<h3>Requirements</h3>
<hr>
<ul>
 <li>PHP 7.2 or better</li>
 <li>Webserver of choice (Nginx is suggested)</li>
 <li><a href="https://repl.it">Repl.it</a> classroom</li>
 <li><a href="https://getcomposer.org">Composer</a> dependency manager</li>
</ul>

<h3>Installation</h3>
<hr>

<div>
1. Clone the project into the directory of your choice:<pre>git clone https://github.com/hudson-b/assignment-manager.git</pre>
</div>

<div>
2. Rename the <i>data.sample</i> directory to <i>data</i>:<pre>mv data.sample data</pre>
       .. or create a new, empty <i>data</i> directory.
</div>

<div>
3. Set the properties on the data directory:<pre>chmod 0777 data -R</pre>
</div>

<div>
4. Use <i>composer</i> to install the dependencies:<pre>composer install</pre>
</div>

<div>
5 (optional). Create a <i>main.users</i> file.  This is a simple text file.  Each line contains the MD5 hash of a <i>username:password</i>.  For example, to add the user <b>test</b> with password <b>thing</b>, create an MD5 of test:thing and add it to <i>main.users</i>.   Use <a href="https://www.md5hashgenerator.com/">this site</a> to create tokens.  Check <i>main.users.sample</i> for an example, it contains the MD5 hash for guest:demo.  If no <i>main.users</i> file exists, the default credentials are guest:demo
</div>




<h3>Project Overview</h3>
<hr>
<dl>
 <dt>common.php</dt>
 <dd>Helper classes and other common functions that are shared across the project.  This includes the File and Data interface classes, and the Logger class for system logging functions.</dd>

 <dt>index.php</dt>
 <dd>The primary server-side handler for the main web interface.  This provides POST and OPTION request handling to manage all aspects of the project, such as rubrics and student file submissions.</dd>

 <dt>webhook.php</dt>
 <dd>This is the endpoint for the Repl.it POST action.  It answers the POST, validates the JSON and stores it in the project’s data collection.</dd>

 <dt>module.js</dt>
 <dd>The primary Javascript object for the project.  The Module object provides standard functions, settings and other common functions for client side actions.</dd>

 <dt>grader.js</dt>
 <dd>The primary Javascript object for grading submissions, Grader includes comprehensive testing functions and analysis features to parse rubrics and grade submissions.</dd>

 <dt>login.get / login.css</dt>
 <dd>The primary HTML and style for the project’s administrative page.</dd>

 <dt>main.get / main.css</dt>
 <dd>The primary HTML and style for the login page.</dd>
</dl>




