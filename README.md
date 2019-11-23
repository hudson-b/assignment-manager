# assignment-manager
Integration / Manager for Repl.it -> Style grading -> Gradebook Export to Moodle

Milestone One Presentation:
https://prezi.com/view/u9u9PJc6mZ8OPWKJFFIo/


Installation
------------

Clone the project into the directory of your choice


Create a data directory, and grant full write permissions:
<pre>
mkdir data
chmod 0777 data
</pre>

Use <i>composer</i> to install the dependencies:
<pre>
composer install
</pre>

Create the <i>main.users</i> file.  This is a simple text file that lists user:password, one per line:
<pre>
user:password
anotheruser:anotherpassword
</pre>


