# assignment-manager
Integration / Manager for Repl.it -> Style grading -> Gradebook Export to Moodle

<h3>Videos</h3>
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
<dl>
 <dt>URL</dt><dd>https://faculty.lynchburg.edu/hudson_b/assignment-manager-demo</dd>
 <dt>User</dt><dd>guest></dd>
 <dt>Password</dt><dd>demo</dd>
</dl>


<h3>Installation</h3>

<ol>

    <li>Clone the project into the directory of your choice.</li>

    <li>Create a data directory, and grant full write permissions:
        <pre>
        mkdir data
        chmod 0777 data
        </pre>
    </li>

    <li>Use <i>composer</i> to install the dependencies:
       <pre>
        composer install
        </pre>
    </li>

    <li>
    Create the <i>main.users</i> file.  This is a simple text file with one MD5 hash per line of username:password. Use <a href="https://www.md5hashgenerator.com/">this</a> to create tokens.
    </li>


    <li>
        Configure <a href="https://docs.repl.it/classrooms/webhooks">Repl</a> to use the <i>webhook.php</i> as the webhook submission endpoint.
    </li>

</ol>


<h3>Project Overview</h3>
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

 <dt>client.js</dt>
 <dd>Client side presentation layer objects, primarily for display formatting and other global includes.</dd>

 <dt>login.get / login.css</dt>
 <dd>The primary HTML and style for the project’s administrative page.</dd>

 <dt>main.get / main.css</dt>
 <dd>The primary HTML and style for the login page.</dd>
</dl>




