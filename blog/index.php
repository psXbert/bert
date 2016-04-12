<?php

/**
	This application is not meant for production use. Its only purpose is to
	serve as a sample code to help you get acquainted with the Fat-Free
	Framework. Padding this blog software with a lot of bells and whistles
	would be nice, but it would also make the entire learning process
	lengthier and more complicated.
**/

// Use the Fat-Free Framework
$main=require_once __DIR__.'/lib/base.php';

/**
	We don't have to "include" or "require" the blog.php class because
	the framework automatically searches the path defined in AUTOLOAD.
	If you want a different path for autoloaded classes, use:-

		F3::set('AUTOLOAD','path/to/autoloaded/classes/');

	The framework can search multiple folders too:-

		F3::set('AUTOLOAD','folder1/,folder2/,main/sub/');

	If you define a static onload() method inside an autoloaded class, it is
	also auto-executed the same way __construct() is called by an object
	instantiating a dynamic class.
**/
$main->set('AUTOLOAD','app/');

/**
	Setting the Fat-Free global variable DEBUG to zero will suppress stack
	trace in HTML error page. If you're still debugging your application,
	you might want to give it a value from 1 to 3. Adjust to your desired
	level of verbosity. The stack trace can help a lot in program testing.
**/
$main->set('DEBUG',3);

// Path to our CAPTCHA font file
$main->set('FONTS','fonts/');

// Path to our templates
$main->set('GUI','gui/');

// Define application globals
$main->set('site','Blog/RSS Demo');
$main->set('data','db/demo.db'); // Can be an absolute or relative path
$main->set('timeformat','r');

// Common inline Javascript
$main->set('extlink','window.open(this.href); return false;');

// Define our main menu; this appears in all our pages
$main->set('menu',
	array_merge(
		array(
			'Home'=>$main->get('BASE')
		),
		// Toggle login/logout menu option
		$main->get('SESSION.user')?
			array(
				'Logout'=>'logout'
			):
			array(
				// About doesn't appear when we're logged in
				'About'=>'about',
				'Login'=>'login'
			)
	)
);

/**
	Let's define our routes (HTTP method and URI) and route handlers.
**/

// Our home page
$main->route('GET /','Blog->show');

// Minify CSS; and cache page for 60 seconds
$main->route('GET /min','Blog->minified',60);

// Cache the 'About' page for 60 seconds; read the full documentation to
// understand the possible unwanted side-effects of the cache at the
// client-side if your application is not designed properly
$main->route('GET /about','Blog->about',60);

// This is where we display the login page
$main->route('GET /login','Blog->login');

	// This route is called when user submits login credentials
	$main->route('POST /login','Blog->auth');

// New blog entry
$main->route('GET /create','Blog->create');

	// Submission of blog entry
	$main->route('POST /create','Blog->save');

// Edit blog entry
$main->route('GET /edit/@time','Blog->edit');

	// Update blog entry
	$main->route('POST /edit/@time','Blog->update');

// Delete blog entry
$main->route('GET /delete/@time','Blog->erase');

// Logout
$main->route('GET /logout','Blog->logout');

// RSS feed
$main->route('GET /rss','Blog->rss');

// Generate CAPTCHA image
$main->route('GET /captcha','Blog->captcha');

// Execute application
$main->run();

// Now, isn't the above code simple and clean?
