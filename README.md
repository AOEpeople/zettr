# Zettr

[![Build Status](https://travis-ci.org/AOEpeople/zettr.svg)](https://travis-ci.org/AOEpeople/zettr)

Author: [Fabrizio Branca](https://twitter.com/fbrnc)

(formerly known as "EnvSettingsTool")

### What's a "setting"?

A 'setting' is everything that's different from one environment to another. During the installation the project needs to 
be 'adapted' to the target environment you're deploying it to. These values can't be hardcoded and they also shouldn't 
have to be changed manually after the installation.

The classic (and obvious) settings are urls, database parameters, maybe cache backend settings and payment provider 
configuration (you're probably using a sandbox in all non-production environments).

But really there are a lot more settings that you might want to control via Zettr:
- Feature flags
- Cache prefixes
- Enabling/disabling modules
- Enabling/disabling stores
- Enabling/disabling users
- Available shipping methods

And even if some specific settings are the same for all of your environments you might want to consider adding them to 
your settings file in order to enforce them being in place every time you run the tool (every time you install a new build)
or in order to roll out values for settings you're introducing in your codebase.

### Warning

Settings that are controlled via Zettr can still be overwritten in Magento. Zettr doesn't "lock" those settings and the
user will not even notice that this value is "remote controlled" and will be overwritten on the next deployment.
 
### Best practice

#### Make sure there are no leftovers by deleting all values in all stores first

(Insert example for % % --delete--)

### Commands

```
Usage:
  zettr.phar apply [--dryRun] [--groups=GROUPS] [--excludeGroups=EXCLUDEGROUPS] <environment> <file1.csv> [<file2.csv>]

Example:
  zettr.phar apply devbox ../Configuration/settings.csv
```

Root dir = CWD (or `--rootDir`)


#### apply
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

### How to specify a handler (1st column of the csv file):
- **Zettr\Handler\Magento\CoreConfigData**: Class name with namespace
- **\Zettr\Handler\Magento\CoreConfigData**: Leading backslash will be trimmed
- **Magento\CoreConfigData**: Class will be looked up in the Zettr\Handler namespace as a fallback
- **Est_Handler_Magento_CoreConfigData**: Legacy handler classes will be converted

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

## Run phpunit

```
tools/phpunit.phar src/
```
 
### Create zettr.phar

```
tools/composer.phar install
tools/box.phar build
```