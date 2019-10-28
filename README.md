# Integrate Toogl Entries with OpenProject
PHP Script to insert Toggl entries inside OpenProject Modules

Description:
This script allow to insert Toggl entries to OpenProject

INSTRUCTIONS

- You must to get the ID of each user inside OpenProject
- You need the API KEY of Toggl of each user (inside profile settings of Toggl)


- The project name inside Toggl and OpenProject must to be the same.
- The descripcion of the Toggl Entrie must to exist like a Module Name inside OpenProject
- If the module to insert time not exist the script insert the time inside a module called "Others" and the time expent will go to this module. *This module must to be created in every projects*


RECOMMENDED

Create a cronjob to execute the script. For example every night.
