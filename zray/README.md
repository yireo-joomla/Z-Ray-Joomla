# Z-Ray-Joomla

This is an extension to add functionality to the Zend Server Z-Ray. 
It will result in additional tab(s) to be presented in the browser.
More information on the usage of this extension can be found on our site:
[www.yireo.com/software/joomla-extensions/zray](https://www.yireo.com/software/joomla-extensions/zray)

### Current state
Version 0.2.8 (stable) = Ready for production. Leave your comment for feature suggestions.

### Requirements
- For version 0.2.4: Zend Server 8.0 with Z-Ray support enabled.
- For version 0.2.5 or higher: Zend Server 8.1 with Z-Ray support enabled.

This extension will only output on Joomla sites with the Z-Ray toolbar enabled.

### Installation
Create a directory `/usr/local/zend/var/zray/extensions/Joomla`, and add the contents of this repo within.

```
    /usr/local/zend/var/zray/extensions/Joomla/zray.php
    /usr/local/zend/var/zray/extensions/Joomla/logo.png
```

The `zray` folder might also be a custom folder (like `/opt/zray`) when using the Z-Ray standalone version without Zend Server.

### Features
* Listing of all rendered Joomla modules (`mod_menu`, etcetera)
* Listing of triggered Joomla events (`onAfterRender`, etcetera)
* Listing of Joomla plugins that catch a triggered event (`plgContentEmailcloak`, etcetera)
* Listing of request data (component-name, view, layout, ID, Itemid, other data)
* Listing of configuration data (version, template name, template parameters, global config)

### Contact
Open an issue in the GitHub repo if you want. Alternatively, contact me at jisse AT yireo AT com or tweet to @yireo. Eat your vegetables.
