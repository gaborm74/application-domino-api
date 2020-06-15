# application-domino-api

## * Requirements
- PHP 7.3+
- Laravel 7.x
- MySQL 8.x
- Your choice of HTTP server

## * Setup
- Start your HTTP with a vhost set to serve your api app directory
	-  For this documentation I use https://domino-api.local as server name
- Start MySQL
	- Create a database for the app, for this example I'll use *domino-api*
	- Create a user and password to have access to this database
	- Update your .env file accordingly
- Update componenets with Composer
<code>$composer update</code>
- Run migration
<code>$php artisan migrate</code>
- Start worker queue
<code>$php artisan queue:work database</code>

#  Usage

## * User and token management

Use the header **Accept: application/json** in every call.

### > Register - POST

https://domino-api.local/api/register
This will create a user you can use to login and get a token to use the API

#### Required fields
| Fields |
| --- |
|name|
|email|
|password|
|password_confirmation|

### > Login - POST
https://domino-api.local/api/login
Login with user credentials, it'll return a token to use with the rest of the calls

#### Required fields
| Fields |
| --- |
|email|
|password|

### > User - GET
http://domino-api.local/api/user
Returns the logged in user's information
#### Required header

|Key|Value|
| --- | --- |
|Authorisation|Bearer \<token\>|

### > Logout - POST
http://domino-api.local/api/logout
Logs the user out and revoke the token
#### Required header
|Key|Value|
| --- | --- |
|Authorisation|Bearer \<token\>|

## * Domino Game

### > Setup - POST
http://domino-api.local/api/game/setup
Sets up the game and returns the game ID
#### Required header
|Key|Value|
| --- | --- |
|Authorisation|Bearer \<token\>|
#### Required fields

| Name | Description |
| --- | --- |
| players | Number of players in the game (2-4) |

#### Response
Returns a UUID you can use in the calls for game specific data

### > List - GET
http://domino-api.local/api/game/list
List all the games in the database. Can be used to get the UUID for a game
#### Required header
|Key|Value|
| --- | --- |
|Authorisation|Bearer \<token\>|

#### Response
Returns all data for each game previously set up

### > Start - POST
http://domino-api.local/api/game/start
Starts the game in the background.
#### Required header
|Key|Value|
| --- | --- |
|Authorisation|Bearer \<token\>|
#### Required fields

| Name | Description |
| --- | --- |
| game_session_id | The game UUID deom 'setup' |

#### Response
Returns a confirmation that the game is running.

### > Status - GET
http://domino-api.local/api/game/status
Gets the status field for the game.
#### Required header
|Key|Value|
| --- | --- |
|Authorisation|Bearer \<token\>|
#### Required fields

| Name | Description |
| --- | --- |
| game_session_id | The game UUID deom 'setup' |

#### Response
Returns the status of the game:

|Status ID| Description|
|---|---|
|0| The game is set up via /api/setup |
|1| The game is ready to run|
|2| The game is dispatched to the queue|
|3| The game job is starting|
|4| The game is created for running|
|5| Players are dealt |
|6| The game has ended|
|101| An error happened, the log is written to **/var/tmp/game_result_\<game UUID\>**|

### > Result - GET
http://domino-api.local/api/game/result
Gets the result of a game
#### Required header
|Key|Value|
| --- | --- |
|Authorisation|Bearer \<token\>|
#### Required fields

| Name | Description |
| --- | --- |
| game_session_id | The game UUID deom 'setup' |

#### Response
Returns the winner and each step of the game.

**Hand**: HTML coded UTF8 chars of domino tiles in the player's hand
**Table**: HTML coded UTF8 chars of played domino tiles on the table
**Boneyard**: HTML coded UTF8 chars of domino tiles left in the boneyard

```json
 "result": {
        "winner": "[1]",
        "steps": {
            "1": {
                "player": 0,
                "hand": "&#x1F050;&#x1F047;&#x1F048;&#x1F03F;&#x1F057;&#x1F046;&#x1F031;",
                "table": "",
                "boneyard": "&#x1F056;&#x1F039;&#x1F061;&#x1F04D;&#x1F05D;&#x1F058;&#x1F054;&#x1F04E;&#x1F055;&#x1F049;&#x1F040;&#x1F05C;&#x1F038;&#x1F05E;&#x1F041;&#x1F05F;&#x1F059;&#x1F04F;&#x1F060;&#x1F05B;&#x1F051;"
            },
            "2": {
                "player": 1,
                "hand": "&#x1F056;&#x1F039;&#x1F061;&#x1F04D;&#x1F05D;&#x1F058;&#x1F054;",
                "table": "",
                "boneyard": "&#x1F04E;&#x1F055;&#x1F049;&#x1F040;&#x1F05C;&#x1F038;&#x1F05E;&#x1F041;&#x1F05F;&#x1F059;&#x1F04F;&#x1F060;&#x1F05B;&#x1F051;"
            },
...
```