# WBrevisions

Some revision based queries

## Installation
Grab in instance from the Wikibase Task repository. Create a "WSrevisions" folder in your Wiki extensions folder and extract the files there.
Add the following line to your localsettings.php : <pre>require_once( "$IP/extensions/WSrevisions/WSrevisions.php" );</pre>

### FAQ
A major revision is a non-minor revision in this example.

#### ws_check_nme

## Example 1
Check pageid 231868 for major revisions in the last 7 days from now
```
{{#ws_check_nme: 231868}}
```
Answer will be "Yes" or "No"

## Example 2
Check if pageid 231868 had major revisions 7 days prior and up to 19-01-2018 00:00 hrs
```
{{#ws_check_nme: 231868|date=2018-01-19|interval=7}}
```
Answer will be "Yes" or "No"

## Example 3
Check if pageid 231868 had major revisions 7 days prior and up to 19-01-2018 00:00 hrs
```
{{#ws_check_nme: 231868|date=2018-01-19|interval=7|count}}
```
Answer will be 0 for none or e.g. 14 if yes (so it will return the number of revisions)

#### ws_size_diff

## Example 1
Get current page size in bytes from page with id 231868 and substract the major revision page size from 7 days ago (return size difference)
```
{{#ws_size_diff: 231868}}
```

## Example 2
Get page size in bytes from page with id 231868 and revision date 2018-01-12 and substract the major revision page size from 14 days ago (return size difference)
```
{{#ws_size_diff: 231868|date=2018-01-12|interval=14}}
```
