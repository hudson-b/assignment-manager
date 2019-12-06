# assignment-manager
Integration / Manager for Repl.it -> Style grading 

<h3>Project Goals</h3>
This project provides a flexible mechanism for grading student code style.  For example, checking the quality of variable names (all lower/upper case), or
verifying that comments are present, or even checking for the usage of a particular technique such a in place modification or a special keyword usage.  The
<i>rubric</i> structure defines a collection of tests that examine the code in any number of ways.  You can store as many rubrics as needed.  
Student code is submitted to the project from the site <i>Repl.it</i> using their <a href="">webhook</a> event.  

<h3>Videos</h3>
<hr>
<dl>
 <dt>Milestone One Presentation</dt>
 <dd>https://prezi.com/view/u9u9PJc6mZ8OPWKJFFIo/</dd>
 <dt>Milestone Two Videos</dt>
 <dd>
Part 1 : Repl.it / Teacher Role : https://youtu.be/E4kbeCdJKYg<br>
Part 2 : Repl.it / Student Role : https://youtu.be/_9f2dhUxQoI<br>
Part 3 : Project demo : https://youtu.be/rAQVYo93CG0<br>
 </dd>
</dl>


<h3>Demo Site</h3>
<hr>
<dl>
 <dt>URL</dt><dd>https://faculty.lynchburg.edu/hudson_b/assignment-manager-demo</dd>
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
<ol>
    <li>
     Clone the project into the directory of your choice:  <pre>git clone https://github.com/hudson-b/assignment-manager.git</pre>
    </li>
    <li>
      Create a data directory, and grant full write permissions:    <pre>mkdir data &&  chmod 0777 data</pre>
      <div>or</div>
      Rename the <i>data.sample</i> directory to <i>data</i>.  This provides sample rubrics and submissions for exploration.
    </li>
    <li>
      Use <i>composer</i> to install the dependencies: <pre>composer install</pre>
    </li>
    <li>
     Create the <i>main.users</i> file.  This is a simple text file:  Each line contains the MD5 hash of a <i>username:password</i>.  For example, to create the user <b>test</b> with password <b>thing</b>, create an MD5 of test:thing and add it to <i>main.users</i>.
     Use <a href="https://www.md5hashgenerator.com/">this site</a> to create tokens.  Check <i>main.users.sample</i> for an example.
    </li>
    <li>
     Configure your <a href="https://repl.it">Repl.it</a> classroom webhook to POST to the <i>webhook.php</i> page.
    </li>
</ol>



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




