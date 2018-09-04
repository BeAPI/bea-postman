# Bea Postman #

## Description ##

WordPress class for replacements and mail sender


## Important to know ##

Usage
-----
There are two use cases for this class, the first one is basic, specifying the recipients, data and message, then send. The other one is to use the replacement methods then send the email.

**Basic**

    <?php
    // Create the sended
    $sender = new Bea_Postman( 'Hi !', 'foo@bar.fr', 'welcome' );

	// Set the data
    $sender->set_data( array( 'first_name' => 'John', 'last_name' => 'Doe' ) );

	// Send the email
	$sender->send(); ?>

There the class will try to find the template in your theme/child theme in :

    path/to/your-theme/bea-postman/welcome-html.tpl
And will try to find the header and the footer (not mandatory) of your email in :

    path/to/your-theme/bea-postman/header-html.tpl
    path/to/your-theme/bea-postman/welcome-html.tpl

The template file is working like this, you put a var between %% like this :

    %%FIRST_NAME%%
And then it's get replaced by the class. If you want you can put replacements tags into your text to replace like this :

    $sender->set_data( array( 'first_name' => 'John %%LAST_NAME%%', 'last_name' => 'Doe' ) );

So the main purpose of this technique is to create replacements on your mail templates and allow you to use options from the backoffice entered by a user and replace them after.

**Advanced**

The advanced way is when you have to internationalize your message but you will not create a file by language you want to support, so use the WordPress i18n API like this :

      <?php
        // Create the sended
        $sender = new Bea_Postman( __( 'Hi !', 'textdomain' ), 'foo@bar.fr', 'welcome' );

    	// Set the data
        $sender->set_data( array( 'first_name' => 'John', 'last_name' => 'Doe' ) );
        $sender->add_data( 'content', __( 'Hello %%FIRST_NAME%% %%LAST_NAME%%,..... ' ) );

    	// Send the email
    	$sender->send(); ?>
The template file will be something like this :

    %%MESSAGE%%
And so on, the message tag is replaced by the replaced message itself replaced and you can send a translated message to your users and you can change the positions of the tags based on the language.

## Changelog ##

### 0.1
* 25 June 2015
* Initial

### 0.1.1
* 12 May 2016
* Fixed email variable name typo

### 0.1.2
* 04 Nov 2016
* Changed the generate_content to public

### 0.1.3
* 04 Sep 2018
* Add function to set email headers
