MotaWord helps you localize and translate your Drupal websites with two technologies: MotaWord Active and MotaWord Drupal plugin. On this page, we will guide you through the installation of our Drupal plugin. You can learn more about [MotaWord Active technology here](https://www.motaword.com/active).

MotaWord Drupal plugin is a provider plugin for the popular TMGMT module – Drupal Translation Management Module. All you need to use MotaWord in your Drupal website is:

    * [TMGMT module](https://drupal.org/project/tmgmt)
    * PHP >=5.6
    * Your MotaWord API keys

We guide you below in the Installation section to gather all of this.

INSTALLATION
------------

Your MotaWord developer account

MotaWord plugin communicates with MotaWord's API to automate your Drupal translations. For this, you will need a developer account and API keys.

    1. Log into your MotaWord account [here](https://www.motaword.com/login/developer). If you don't have a MotaWord developer account yet, you can register [here](https://www.motaword.com/register/developer).

    2. Create an "app" in your MotaWord developer dashboard if you haven't already. Click "New +":

    3. Give a name for your MotaWord app (integration), and select "Drupal" type, click "Create":

    4. That's it! Click "View Keys" to access your "API client ID/application ID" and "API client secret/application secret key":

    5. We will later use this ID and secret key in your Drupal plugin configuration.

Install Translation Management (TMGMT) module in Drupal

MotaWord Drupal plugin is a provider plugin for the popular Translation Management (TMGMT) module. Before installing the MotaWord plugin, we need to install the TMGMT module first.

Learn more about the TMGMT module here: https://drupal.org/project/tmgmt

    1. On TMGMT page on Drupal.org, select the TMGMT module version compatible with your Drupal version. Copy its `.tar.gz` file URL.

    2. In your Drupal administrator panel, go to Extend > + Install new module. Paste the `.tar.gz` URL in the input and install the plugin.

    3. We need to enable more modules for a comprehensive localization experience for your Drupal site. Learn more about those [modules here and activate them](https://www.drupal.org/node/1490004).

    4. For further documentation on getting started with TMGMT, you can [take a look here](https://www.drupal.org/node/1490024).

Install MotaWord Drupal plugin in Drupal

MotaWord Drupal plugin (a TMGMT provider) lives here: https://www.drupal.org/project/tmgmt_motaword

    1. Just like we did for the generic TMGMT module, download tmgmt_motaword module, or copy the `.tar.gz` URL for your Drupal version.

    2. In your Drupal administrator panel, go to Extend > + Install new module. Paste the `.tar.gz` URL in the input or upload the zip package and install the plugin.

    3. Let's activate MotaWord provider for TMGMT. You must at this point have a new menu on your Drupal administration panel called "Translation". Go to "Translation" menu, then "Providers":

    4. You should see MotaWord as your translation provider. Click "Edit" next to it.

    5. Remember the Drupal app we created in your MotaWord developer dashboard? We are going to use that now. Copy the application ID and secret keys from your MotaWord developer dashboard and paste them in provider edit page in Drupal administration panel:

    6. Click "Save", and that's it!

You are now ready to use MotaWord integration in your Drupal instance. We suggest enabling "Sandbox" in the Edit screen in Drupal translation provider settings page. This way, you won't be charged for your translation orders during testing (and we won't work on them).

Once you are ready for a full-fledged test, we are here to walk you through the simple steps of ordering and monitoring translations.

