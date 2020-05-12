# ccollins Updater
Template for creating wordpress plugins that auto-update through github. Also secures the token, user, project name in your wordpress database.

## Using ccollins updater
This works with public and private github repositories. To connect your plugin to github just install it in your wordpress site as a plugin then you'll see the admin menu "ccollins updater" click that and you'll see the setting dashboard. The settings are, 
* username - Your github repository where you will post your tag release (make sure the tag release matches the release in your plugin).
* Repository - The name of your plugin project on github. 
* License Key - (optional) only applies to private repositories. Your personal access token from github. *Note:* For private repositories you will need to generate a personal access token and give it the *repo* scope. This can be done in your github Account Settings > Developer Settings > Personal Access Tokens. 

### Useful Links 
* [How To Deploy WordPress Plugins With GitHub Using Transients](https://www.smashingmagazine.com/2015/08/deploy-wordpress-plugins-with-github-using-transients/)

### Credit 
* [Christopher Collins](https://ccollins.io) *Lead Developer*