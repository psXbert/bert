<?php

/**
	Not ideal, but we're lumping all the route handlers in one class so it's
	easier to understand the application logic and how the methods interact
	with the index.php main controller.
**/
class Blog extends F3instance {

	// Display our home page
	function show() {
		// Retrieve blog entries
		$blog=new Axon('blogs');
		$this->set('entries',$blog->afind());
		// Use the home.htm template
		$this->set('pagetitle','Home');
		$this->set('template','home');
	}

	// Render the about.htm template
	function about() {
		$this->set('pagetitle','About');
		$this->set('template','about');
	}

	// Render the login.htm template
	function login() {
		// Reset session in case someone else is logged in
		$this->clear('SESSION');
		// Render template
		$this->set('pagetitle','Login');
		$this->set('template','login');
	}

	// User authentication
	function auth() {
		// Reset previous error message, if any
		$this->clear('message');
		// Form field validation
		$this->checkID();
		$this->password();
		if (isset($_SESSION['captcha']))
			$this->code();
		if (!$this->exists('message')) {
			// No input error; check values
			if (preg_match('/^admin$/i',$_POST['userID']) &&
				preg_match('/^admin$/i',$_POST['password'])) {
				// User ID is admin, password is admin - set session variable
				// Fat-Free auto-starts a session when you use F3::set() or
				// F3::get(). F3::clear() automatically destroys a session
				// variable or even an entire session
				$this->set('SESSION.user',$_POST['userID']);
				// Return to home page; but now user is logged in
				$this->reroute('/');
			}
			else
				$this->set('message','Invalid user ID or password');
		}
		// Display the login page again
		$this->login();
	}

	// End the session
	function logout() {
		// Destroy the session
		$this->clear('SESSION');
		// Redirect to Home page
		$this->reroute('/');
	}

	// Render the blog.htm template
	function create() {
		$this->set('pagetitle','Add Blog Entry');
		$this->set('template','blog');
	}

	// Save new blog entry
	function save() {
		// Reset previous error message, if any
		$this->clear('message');
		// Form field validation
		$this->title();
		$this->entry();
		if (!$this->exists('message')) {
			// No input errors; add record to database
			$blog=new Axon('blogs');
			$blog->copyFrom('POST');
			$blog->time=date($this->get('timeformat'));
			$blog->save();
			// Return to home page; new blog entry should now be there
			$this->reroute('/');
		}
		// Display the blog form again
		$this->create();
	}

	// Edit existing blog entry
	function edit() {
		// Retrieve matching record
		$blog=new Axon('blogs');
		// Parameterized query
		$blog->load(
			array(
				'time=:stamp',
				array(
					':stamp'=>date(
						$this->get('timeformat'),$this->get('PARAMS.time')
					)
				)
			)
		);
		if (!$blog->dry()) {
			// Populate POST global with retrieved values
			$blog->copyTo('POST');
			// Render blog.htm template
			$this->set('pagetitle','Edit Blog Entry');
			$this->set('template','blog');
		}
		else
			// Invalid blog entry; display our 404 page
			$this->error(404);
	}

	// Update existing blog entry
	function update() {
		// Reset previous error message, if any
		$this->clear('message');
		// Form field validation
		$this->title();
		$this->entry();
		if (!$this->exists('message')) {
			// No input errors; update record
			$blog=new Axon('blogs');
			$blog->load(
				array(
					'time=:stamp',
					array(
						':stamp'=>date(
							$this->get('timeformat'),$this->get('PARAMS.time')
						)
					)
				)
			);
			$blog->copyFrom('POST');
			$blog->time=date($this->get('timeformat'));
			$blog->save();
			// Return to home page
			$this->reroute('/');
		}
		// Display the blog form again
		$this->edit();
	}

	// Delete blog entry
	function erase() {
		if ($this->get('SESSION.user')) {
			$blog=new Axon('blogs');
			$blog->load(
				array(
					'time=:stamp',
					array(
						':stamp'=>date(
							$this->get('timeformat'),$this->get('PARAMS.time')
						)
					)
				)
			);
			$blog->erase();
			// Return to home page
			$this->reroute('/');
		}
		else {
			// Render blog.htm template
			$this->set('pagetitle','Delete Blog Entry');
			$this->set('template','blog');
		}
	}

	// RSS feed
	function rss() {
		// Retrieve blog entries
		$blog=new Axon('blogs');
		$this->set('entries',$blog->afind());
		/**
			We could have just as easily accomplished the above using the
			following:
				F3::sql('SELECT title,entry,time FROM blogs;');
				F3::set('entries',$this->get('DB->result'));
		**/
		echo Template::serve('rss.xml','text/xml');

	}

	// Display a CAPTCHA image
	function captcha() {
		Graphics::captcha(180,75,5);
	}

	/**
		We can expect the link specified in layout.htm to be routed here:-
			<link rel="stylesheet" type="text/css"
				href="/min?base=gui/css/&amp;files=demo.css"/>

		Notice that we only specified the following in our controller:-
			$this->route('GET /min','blog::minified');

		The Web::minify method combines all our comma-separated files
		(although we just have demo.css in our example) and strips them of
		all whitespaces and comments. The output is then gzipped and given a
		far-future expiry date so we get the squeeze every ounce of
		performance from our server.

		You can name the variables 'base' and 'files' any way you like. But
		make sure that the path pointed to by 'base' (or whatever variable
		you replace it with is relative to your Web root's index.php.
	**/
	function minified() {
		if (isset($_GET['base']) && isset($_GET['files'])) {
			$_GET=$this->scrub($_GET);
			Web::minify($_GET['base'],explode(',',$_GET['files']));
		}
	}

	// Validate user ID
	function checkID() {
		$this->input('userID',
			function($value) {
				if (!F3::exists('message')) {
					if (empty($value))
						F3::set('message','User ID should not be blank');
					elseif (strlen($value)>24)
						F3::set('message','User ID is too long');
					elseif (strlen($value)<3)
						F3::set('message','User ID is too short');
				}
				// Convert form field to lowercase
				$_POST['userID']=strtolower($value);
			}
		);
	}

	// Validate password
	function password() {
		$this->input('password',
			function($value) {
				if (!F3::exists('message')) {
					if (empty($value))
						F3::set('message','Password must be specified');
					elseif (strlen($value)>24)
						F3::set('message','Invalid password');
				}
			}
		);
	}

	// Validate CAPTCHA verification code
	function code() {
		$this->input('captcha',
			function($value) {
				if (!F3::exists('message')) {
					if (empty($value))
						F3::set('message',
							'Verification code required');
					elseif (strlen($value)>strlen($_SESSION['captcha']))
						F3::set('message',
							'Verification code is too long');
					elseif (strtolower($value)!=$_SESSION['captcha'])
						F3::set('message',
							'Invalid verification code');
				}
			}
		);
	}

	// Validate title
	function title() {
		$this->input('title',
			function($value) {
				if (!F3::exists('message')) {
					if (empty($value))
						F3::set('message','Title should not be blank');
					elseif (strlen($value)>127)
						F3::set('message','Title is too long');
					elseif (strlen($value)<3)
						F3::set('message','Title is too short');
				}
				// Do post-processing of title here
				$_POST['title']=ucfirst($value);
			}
		);
	}

	// Validate blog entry
	function entry() {
		$this->input('entry',
			function($value) {
				if (!F3::exists('message')) {
					if (empty($value))
						F3::set('message','Entry should not be blank');
					elseif (strlen($value)>32768)
						F3::set('message','Entry is too long');
					elseif (strlen($value)<3)
						F3::set('message','Entry is too short');
				}
			},
			// Allow these HTML tags in the textarea, so we can make it
			// compatible with TinyMCE, CKEditor, etc.
			'p,span,b,i,strong,em,u,br,a,ul,li'
		);
	}

	/**
		The beforeroute() event handler is automatically executed by the
		framework if found inside an autoloaded class like this file.
	**/
	function beforeroute() {
		/**
			If our database doesn't exist, create our SQLite schema; we'll do
			it here programmatically; but this can be done outside of our
			application.

			Create database connection; The demo database is within our Web
			directory but for production use, a non-Web accessible path is
			highly recommended for better security.

			Fat-Free allows you to use any database engine - you just need
			the DSN so the framework knows how to communicate with it.
			Migrating to another engine should be next to easy. If you stick
			to the standard SQL92 command set (no engine-specific
			extensions), you just have to change the next line. For this
			demo, we'll use the SQLite engine, so there's no need to install
			MySQL on your server.
		**/
		F3::set('DB',new DB('sqlite:{{ @data }}'));
		if (!file_exists($this->get('data')))
			// SQLite database doesn't exist; create it programmatically
			DB::sql(
				/**
					If an array is passed to the $this->sql() method, the
					framework automatically switches to batch mode; Any
					error that occurs during execution of this command
					sequence will rollback the transaction. If successful,
					Fat-Free issues a SQL commit.
				**/
				array(
					'CREATE TABLE blogs ('.
						'title TEXT,'.
						'entry TEXT,'.
						'time TEXT,'.
						'PRIMARY KEY(time)'.
					');',
					'CREATE TABLE comments ('.
						'blogref TEXT,'.
						'comment TEXT,'.
						'time TEXT,'.
						'PRIMARY KEY(blogref,time)'.
					');'
				)
			);
	}

	/**
		The afterroute() event handler is automatically executed by the
		framework if found inside an autoloaded class like this file.
	**/
	function afterroute() {
		// Serve master template; layout.htm is located in the directory
		// pointed to by the GUI global variable
		echo Template::serve('layout.htm');
	}

}
