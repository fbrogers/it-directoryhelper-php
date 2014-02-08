Example Usage for DirectoryHelper
=================================

Create a DirectoryHelper object first!

```php
<?php $helper = new DirectoryHelper('it'); ?>
```

When creating a new object, a valid site slug must be passed as a parameter. Valid site slugs can be viewed from the Directory Administration console. If the slug is valid, the constructor will automatically connect via cURL to the JSON feed, give it 10 seconds to respond, and then download the data, separating each piece into private properties and sub-classes. If successful, they should immediately ready for use via the HTML accessors.

If being used with the Template Engine, the recommended method is to have the engine create and store a DirectoryHelper object for you, like so:

```php
<?php $data->site_directory_helper('it'); ?>
```

All methods are then accessible after using the accessor to the property for the object, like so:

```php
<?= $data->get_directory_helper()->PrintNews(); ?>
```

This ensures that the object is accessible on all pages; otherwise, you'll have to create the object on each page. Neither method is incorrect, but you may find it difficult to implement site-wide alerts and other bells and whistles without having the object load prior to the page load.

Alerts
------

Alerts contain the following properties: title, message, url, isPlanned, and isSiteWide. Hopefully these properties are fairly self-explanatory.

### Print Single Alert

Typically, there should only be a single alert in the JSON feed, so this is the method that should be used most often. It is worth noting that by default, the SDES Template Engine outputs alerts automatically in all its frames, so unless you need an alert on a non-standard page, it may not be necessary to implement.

```php
<?= $helper->PrintAlert(); ?>
```

This will output an HTML string with a single alert, styled either as .cautionbar (for planned alerts) or .alertbar (for unplanned alerts). Sample HTML output looks like this:

```html
<div class="cautionbar">
  <p>
    <strong>{{{alert->title}}}:</strong>
    {{{alert->message}}}
  </p>
</div>
```

The text of the message will be wrapped in an anchor if the url property is set. In case you want to print all alerts in the JSON (there should rarely be more than a single alert), use this:

```php
<?= $helper->PrintAlerts(); ?>
```

### Print Site Alert

```php
<?= $helper->PrintSiteAlert(); ?>
```

This accessor is more suited for header includes and other side-wide applications. It will print an alert only if the isSiteWide boolean property is true. In the Template Engine, a custom HTML accessor evaluates whether to display an alert using a combination of isSiteWide and the page being loaded.

Documents
---------

```php
<?= $helper->PrintDocument('catalog'); ?>
```

PrintDocument() is the only available accessor for documents (as the structure is very simple). If the slug is found in the JSON feed, the following HTML will be produced:

```html
<a href="{{{directory_uri}}}{{{document->url}}}">{{{document->name}}}</a>
```

If the document is marked inactive inside the Directory, it will not appear in the JSON feed, and the PHP printing the document anchor will return an empty string. While not required, be sure to mark any common container elements with the following CSS to prevent glitchiness:

```css
li:empty{
  display: none;
}
```

News
----

The news section comes in two pieces: summary articles (PrintNews) and large billboards (PrintBillboard). Regardless the display format, each article will contain a title, strapline, and summary, and optionally may contain a url, billboard image, or an extended story.

### Print Summary Articles

```php
<?= $helper->PrintNews(); ?>       //prints all articles, excluding those with billboard images
<?= $helper->PrintNews(true); ?>   //prints all articles, including those with billboard images
```

This will return all articles in the JSON feed as HTML-formatted summaries, each spaced by a separator div. It accepts a single parameter (default is false) that indicates whether articles with billboards should be output. This allows you to print separate articles in the news feed and the billboard or repeat them.

The article summary may contain raw html but will be filtered down using strip_tags and the root class property allowed_html.

```html
<div class="news">
  <img src="{{{directory_uri}}}{{{article->thumb}}}" alt="thumb">
  <div class="news-content">
    <div class="news-title bullets">
      <a href="{{{directory_uri}}}{{{article->url}}}">{{{article->title}}}</a>
    </div>
    <div class="news-strapline">
      {{{article->strapline}}}
    </div>
    <div class="datestamp">
      {{{article->posted}}} by {{{article->user}}}
    </div>
    <div class="news-summary">
      {{{article->summary}}}
    </div>
  </div>
</div>
<div class="hr-blank"></div>
```

If the news article does not contain a URL, the {{{article->title}}} will not be wrapped in an anchor and the div.news-title will not contain a bullets class.

After the last article, this HTML will output:

```html
<div class="top-b"></div>
<div class="datestamp">
  <a href="{{{archive_uri}}}/{{{slug}}}">&raquo;News Archive</a>
</div>
```

This will link to a view in the Directory that will use the same article summary HTML, but will give access to all previously-stored articles, not just those in the JSON feed.

#### Option: Replace Blank User

```php
<?php $helper->ReplaceBlankUser('Mane Six'); ?>
```

If a news article's user property is null (meaning it was posted by an admin user not in the user table or the user has been removed from the system), it will typically be filled with {{{blank_user}}} from the config file. However, on an per-object basis, that text can be substituted using this method. This is useful if a department or office dislikes the default text.

### Print Billboard Slides

```php
<?= $helper->PrintBillboard(); ?>
```

This accessor uses the same feed and same properties but produces a Nivo Slider billboard from articles with the billboard property set:

```html
<!-- boilerplate for billboard -->
<div id="slate_container">
  <div id="slate">
    <div id="slider">
    
      <!-- single article -->
      <a href="{{{article->url}}}">
        <img src="{{{directory_uri}}}{{{article->billboard}}}" alt="thumb" title="#news{{{article->id}}}" />
      </a>

    </div>
  </div>
</div>

<!-- single article -->
<div id="news{{{article->id}}}" class="nivo-html-caption">
  <div class="nivo-padding">
    <div class="nivo-title">{{{article->title}}}</div>
    <div class="nivo-strapline">{{{article->strapline}}}</div>
  </div>
</div>
```

If no articles contain a populated billboard property, this method will output an empty string.

Roles and Staff Members
-----------------------

To keep the database schema simpler, all staff members are contained with roles. However, there are multiple options to print out variations of the staff list.

### Print Staff

PrintStaff() will always return the complete list of staff from each role, with the first parameter controlling the visibility of the role headers.

```php
<?= $helper->PrintStaff(); ?>       //prints all staff as one list
<?= $helper->PrintStaff(true); ?>   //prints all staff grouped by role
```

This will return all staff members in the JSON feed as HTML-formatted summaries, each spaced by a separator div. The staff details may contain raw html but will be filtered down using strip_tags and the root class property allowed_html.

```html
<div class="staff">
  <img src="{{{directory_uri}}}{{{staff->thumb}}}" alt="thumb" />
  <div class="staff-content">
    <div class="staff-name">{{{staff->name}}}</div>
    <div class="staff-title">{{{staff->title}}}</div>
    <div class="staff-email">
      <a href="mailto:{{{staff->email}}}">{{{staff->email}}}</a>
    </div>
    <div class="staff-phone">{{{staff->phone}}}</div>
    <div class="staff-details">
      {{{staff->details}}}
    </div>
  </div>
</div>
<div class="hr-blank"></div>
```

If true is passed as a parameter, this HTML will print at the beginning of each role:

```html
<div class="staff-role">{{{role->name}}}</div>
```

### Print a Single Role

```php
<?= $helper->PrintRole('Faculty'); ?>
```

Use this method to essentially call PrintStaff(false) for a single named role (the first parameter). This is useful if you want to construct sub-sets of the Staff data, a single page for one role, etc.

### Option: Collapse Staff Details

```php
<?php $helper->StaffCollapsed(true); ?>
```

By using the following option set, any long div.staff-details will use jQuery to collapse the contents and place a [Read More] link beneath it, which will expand the contents. Useful if sites have very large biographies or lists.