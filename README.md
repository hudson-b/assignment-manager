# assignment-manager
Integration / Manager for Repl.it -> Style grading -> Gradebook Export to Moodle

Milestone One Presentation:
https://prezi.com/view/u9u9PJc6mZ8OPWKJFFIo/

Milestone Two Videos:
Part 1 : Repl.it / Teacher Role : https://youtu.be/E4kbeCdJKYg
Part 2 : Repl.it / Student Role : https://youtu.be/_9f2dhUxQoI
Part 3 : Project demo : https://youtu.be/rAQVYo93CG0


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

Create the <i>main.users</i> file.  This is a simple text file with one MD5 hash per line of username:password. Use <a href="https://www.md5hashgenerator.com/">this</a> to create tokens.

<pre>
d3c3b5cb55d3c6d0c6122eedccc3dcf3
</pre>


