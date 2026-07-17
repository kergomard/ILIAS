# How to Represent Text in ILIAS?

As an application with content management functionality, ILIAS handles text in
various locations: on handcrafted pages, in mails, in feedbacks, comments and
forums and in various other locations.

Currently, text in ILIAS is not abstracted in any way: It is passed as string.
This causes various problems, especially when text is passed between components,
that pop up from time to time. Does this text contain formatting in HTML? Can
it be used with or without html escaping, or was HTML escaping already done by
the other component? Or will it be performed later? Can I output my text in this
or that context without introducing security problems, formatting problems or
what not?

This paper looks to propose an idea how this situation can be improved.


## What do we Mean by "Text"?

Certainly, we are not looking to target any `string` in the ILIAS codebase. There
are various locations where `string`s are used for other purposes: as ids, as part
of controlled vocabularies, as input from users that needs to be parsed. So what
do we really target when we say "text" in this paper?

**We understand text to be sequences of words of an unspecified length, possibly
containing information about its structure (like paragraphs, headings or lists).
The information content of these sequences of words are of no interest to the
application, but are explicitely targeting the user of the application. The
structure is required to derive formatting for various output contexts.**

Although this might leave some gray areas, this definition could be used to decide
if a given piece of information is or isn't text, e.g.:

* **login names of users**: *no text*, because the information they contain is 
of interest to the application since we need it to derive which user just logged 
in. Additionally login names of users will be short mostly just one word long.
* **content of a mail**: *text* because they convey information to the receiving 
person, but have no informational value for the application. The content of a mail 
contains words and additional structuring is possible and common. The actual 
information content does matter for the receiving person, but not for the application.
* **titles of objects**: *text*, because sequences of words are possible, even 
though we do not provide any means to further structure them. Titles of objects 
do not transport any information for the application (with the notable exception 
of WebDAV, where the usage of text to convey information to the application leads 
to problems with uniqueness and encoding), but are aimed at users.
**content of the page editor**: *no text*, because the information it contains 
cannot be reduced to a structured sequence of words. Content of the page editor 
may contain text, tough. 


## Requirements for Text Handling

To provide ILIAS developers with a tool set to solve the outlined problems with the
current `string`-based text handling approach, a new approach needs to implement
the following requirements:

* For a given piece of text it should be known at any time which markup is used
in the underlying string representation of the text to add structure to it.
* For a given piece of text it should be known at any time which structure elements
could be used in the text.
* On programmatic interfaces it should be possible to specify which structure and
markup is required when passing text.
* The tool set should support building user interfaces to input text with a specified
markup and structure (but actually building said interfaces is out of scope here).
* It should be possible to convert all texts to certain baseline representations.
These are plain text (as this is a baseline that is supported in every interesting
target context) and HTML (since this is the markup for browsers, the main environment
of our users). These conversions may be lossy.
* It may be possible to convert some text available in one representation into some 
other representation but in general it is only expected that every text can be 
converted to HTML and plain text.
* The facility MUST not interfere with the input of `Moustache` as any input MAY 
contain `Moustache` placeholders that MUST be passed through unchanged to the 
output.


## Approach

A facility implementing a growing subset of the requirements is available in the 
component `Data`, using the conventions and standards of that library.
Conversions are available via the `Refinery`.

### Define Structure Options

To make it possible to programmatically talk about structuring options for text,
a central `enum` defines the structring options available to text:

```php
class Structure
{
    // heading 1-6 are cases for <h1> to <h6>
    case HEADING_1 = "h1";
    case HEADING_2 = "h2";
    /*...*/
    case BOLD = "b";
    case ITALIC = "i";
    case UNORDERED_LIST = "ul";
    /* ... */
}
```

### Define Markup

Text is represented as `string` in memory. Since we do not care about the
information or specific structure of a given text, more complex representations 
e.g. as abstract syntax trees are unnecessary. Text might temporarily be 
transformed to non-`string` representations during conversions from one 
representation to another, but these representations MUST be kept local to the
conversion.

The set of available markup is kept static. The different markup
classes might become carriers for markup specific methods (e.g. escaping...).
Currently the `interface` for `Markup` just functions as a tag.

```php
interface Markup
{
}

class HTML implements Markup
{
    public function restrictUsedTags(string $in, array $tags) : string; // for example
}
```

### Define Shapes for Text

The `Shape`s are the workhorses of the tool set. A shape bundles information about 
markup and structuring options. It can produce text data from raw string input 
and convert given data to other shapes. We want to keep the available shapes to
a bare minimum to keep the available options clear and predictable. Currently
only a markdown family is provided containing a single implementation
`MarkdownShape`.

```php
interface Shape 
{
    /**
     * @throws \InvalidArgumentException if $text does not match format. 
     */
    public function toHTML(Text $text) : HTMLText;

    /**
     * @throws \InvalidArgumentException if $text does not match format. 
     */
    public function toPlainText(Text $text) : PlainText;

    public function getMarkup() : Markup;

    /**
     * @return mixed[] consts from Structure
     */
    public function getSupportedStructure() : array;

    public function fromString(string $text) : Text;
}

class MarkdownShape implements Shape
{
    /* will implement all Shape-methods except for `getSupportedStructure` */
}

/* ... */

```

### Define Classes for Text on top of Shapes

Since Shapes do not contain a concrete content, we currently could not hint on some
desired text and shape on interfaces. The `Shape`s and some concrete content is 
bundled into classes for text. These classes mostly repeat the class structure 
from families and wire up methods from there for ease use and checking.

To provide a future proof base for text handling, we use a multibyte
representation for the texts in the string. Accordingly `mb_` string methods
MUST be used to process the raw strings.


## Usage

Consumers of the tool set outlined above will mostly come into contact with the 
classes for text. These can be used to define broad or narrow restrictions on 
texts that are passed to certain components. This could look like this:

```php

class ilObject
{
    /* ... */
    public function setTitle(PlainText $title) : void;
    public function getTitle() : PlainText;
    /* ... */
}

class ilMail
{
    /* ... */
    public function setBody(SimpleDocumentMarkdownText *body) : void;
    /* ... */
}

```

There are some components that will want to work with the tool set more closely:
The UI components, e.g., are expected to make heavy use of the `Shapes` to build
inputs.


## Limitations

This proposal comes with known limitations:

* This is not looking to represent all of HTML. This is about texts (according to 
* the definition given above), not HTML.
* This is not looking to represent all possibilities of the Page Editor (see 
previous point). Instead we expect this to be used in components of the Page 
Editor.
* This is not looking to provide inputs for the various text shapes. This should 
be tackled in the UI framework. Instead this facility provides a tool set to 
talk about the shapes and their requirements to build said inputs.
* This is not looking to provide text processing capabilities that look into the 
actual content of texts. Things like spell checking are out of scope here.
* This is not looking to allow for arbitrary conversions between text shapes or 
markups. There are tools that are looking to do so, but these are complex projects 
in their own right.
* This is not looking to provide functionality for supporting multiple languages 
or localisation (as a special case of "looking into the actual content").
