# Zettr

Author: [Fabrizio Branca](https://twitter.com/fbrnc)

(formerly known as "EnvSettingsTool")

What's a "setting"?
- classic: db parameters, urls, sandbox
What's an environment?

Commands
- apply
  - usage...

File format
- fixed columns 1-4
- optional group
- optional default

also used to enforce settings via version control
Settings injection
Why csv?
- see values next to each other

used for Magento, but not restricted to

What's a handler?

fixed set of 3 param 
- different meaning per handler
special feature to make csv file more efficient
- loops
- variables
- reference other values
- fallbacks
- groups
comments,
ignore errors,...
What's a handler?
- Generic ones
- Magento specific ones
Shortcuts specifying a handler in the csv

How to 
- include this in your projects
  - composer
  - add phar
  - add settings file
  - include in build script (point to magento deploy scripts)
- add a new handler
- build the phar
- import db first (chicken and egg problem)
- run unit tests