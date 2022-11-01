#HIBP

This app is not affilated with haveibeenpwned.com. It is just an app that consumes the API.

## Setup

0. Install the app
1. head over to https://haveibeenpwned.com/API/Key
2. Get yourself an API key
3. run: `./occ hibp:set-api-key <YOUR-API-KEY>`
4. Profit

## Whwat does the app do?

It will check all your users daily against the publicly searchable breaches of HIBP.
If they appear in a breach they will get a notification.
Note that this means that upon enabling the app your users can get quite some notifications.
