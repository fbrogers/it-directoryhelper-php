#IT Directory Helper (PHP)

- Original Developer: Jordan Rogers
- Creation Date: February 2014
- Technology: PHP
- External Dependencies: [`it-sdes-directory`](https://github.com/ucf-sdes-it/it-sdes-directory)

A PHP class that consumes site feeds from the SDES Directory CMS and displays them as HTML variants.

##Releases

v1.0 (February 2014)

> - Initial launch

##Business Case Summary

When the SDES Directory CMS was developed, it was designed to output a JSON feed of data per a site "slug", or primary identifier. However, this was just data; code needed to be developed to output this data as styled HTML. The decision was made to create a PHP class that each site could use to consistently output this data as HTML.

##Functional Summary

This class is meant to exist in a centralized location and can be included in any PHP script. The main file should sit beside a "config.ini" file, populated with configuration fields, including the install directory, the location of the SDES Directory JSON feed, and other options.

Example usage of the methods can be found in [the examples.md file](examples.md).

##User Profiles

###SDES IT Developer

- Include in any PHP script via ```require_once()```
- Utilize methods to output HTML as needed

##External Dependencies

###SDES Directory JSON Feed

As this is basically a helper tool for the [`it-sdes-directory`](https://github.com/ucf-sdes-it/it-sdes-directory), the SDES Directory feed is required. In the event of an error in the JSON, the Directory should output a complete, if blank, JSON schema to avoid exceptions.

##Other Notes

None of note.

##Future Recommendations

None of note.
