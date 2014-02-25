# soundcloud-shortcode

WordPress plugin for converting SoundCloud shortcodes into Embedded Players.


## Running Unit Tests

1. Install [PHPUnit](https://github.com/sebastianbergmann/phpunit)

    - OS X:

    		$ brew install phpunit

2. Run tests

	    $ phpunit


## Deploying to [WordPress Plugins](https://wordpress.org/plugins/)

1. Push your latest code changes to wordpress.org:

    	$ git svn dcommit --username=YOUR_WORDPRESS_ORG_USERNAME

2. Tag your release:

	    $ git svn tag 1.0.2

	This will create /tags/1.0.2 in the remote SVN repository and copy all the files from the remote /trunk into that tag. This allows people to use an older version of the plugin.

3. Tag your release in Git:

   		 $ git tag 1.0.2 && git push --tags
