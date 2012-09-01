<?php
#
# Markdown Extra  -  A text-to-HTML conversion tool for web writers
#
# PHP Markdown & Extra
# Copyright (c) 2004-2012 Michel Fortin  
# <http://michelf.com/projects/php-markdown/>
#
# Original Markdown
# Copyright (c) 2004-2006 John Gruber  
# <http://daringfireball.net/projects/markdown/>
#


define( 'MARKDOWN_VERSION',  "1.0.1o" ); # Sun 8 Jan 2012
define( 'MARKDOWNEXTRA_VERSION',  "1.2.5" ); # Sun 8 Jan 2012


#
# Global default settings:
#

# Change to ">" for HTML output
@define( 'MARKDOWN_EMPTY_ELEMENT_SUFFIX',  " />");

# Define the width of a tab for code blocks.
@define( 'MARKDOWN_TAB_WIDTH',     4 );

# Optional title attribute for footnote links and backlinks.
@define( 'MARKDOWN_FN_LINK_TITLE',         "" );
@define( 'MARKDOWN_FN_BACKLINK_TITLE',     "" );

# Optional class attribute for footnote links and backlinks.
@define( 'MARKDOWN_FN_LINK_CLASS',         "" );
@define( 'MARKDOWN_FN_BACKLINK_CLASS',     "" );


#
# WordPress settings:
#

# Change to false to remove Markdown from posts and/or comments.
@define( 'MARKDOWN_WP_POSTS',      true );
@define( 'MARKDOWN_WP_COMMENTS',   true );



### Standard Function Interface ###

@define( 'MARKDOWN_PARSER_CLASS',  'MarkdownExtra_Parser' );

function Markdown($text) {
#
# Initialize the parser and return the result of its transform method.
#
	# Setup static parser variable.
	static $parser;
	if (!isset($parser)) {
		$parser_class = MARKDOWN_PARSER_CLASS;
		$parser = new $parser_class;
	}

	# Transform text using parser.
	return $parser->transform($text);
}


### WordPress Plugin Interface ###

/*
Plugin Name: Markdown Extra
Plugin URI: http://michelf.com/projects/php-markdown/
Description: <a href="http://daringfireball.net/projects/markdown/syntax">Markdown syntax</a> allows you to write using an easy-to-read, easy-to-write plain text format. Based on the original Perl version by <a href="http://daringfireball.net/">John Gruber</a>. <a href="http://michelf.com/projects/php-markdown/">More...</a>
Version: 1.2.5
Author: Michel Fortin
Author URI: http://michelf.com/
*/

if (isset($wp_version)) {
	# More details about how it works here:
	# <http://michelf.com/weblog/2005/wordpress-text-flow-vs-markdown/>
	
	# Post content and excerpts
	# - Remove WordPress paragraph generator.
	# - Run Markdown on excerpt, then remove all tags.
	# - Add paragraph tag around the excerpt, but remove it for the excerpt rss.
	if (MARKDOWN_WP_POSTS) {
		remove_filter('the_content',     'wpautop');
		remove_filter('the_content_rss', 'wpautop');
		remove_filter('the_excerpt',     'wpautop');
		add_filter('the_content',     'mdwp_MarkdownPost', 6);
		add_filter('the_content_rss', 'mdwp_MarkdownPost', 6);
		add_filter('get_the_excerpt', 'mdwp_MarkdownPost', 6);
		add_filter('get_the_excerpt', 'trim', 7);
		add_filter('the_excerpt',     'mdwp_add_p');
		add_filter('the_excerpt_rss', 'mdwp_strip_p');
		
		remove_filter('content_save_pre',  'balanceTags', 50);
		remove_filter('excerpt_save_pre',  'balanceTags', 50);
		add_filter('the_content',  	  'balanceTags', 50);
		add_filter('get_the_excerpt', 'balanceTags', 9);
	}
	
	# Add a footnote id prefix to posts when inside a loop.
	function mdwp_MarkdownPost($text) {
		static $parser;
		if (!$parser) {
			$parser_class = MARKDOWN_PARSER_CLASS;
			$parser = new $parser_class;
		}
		if (is_single() || is_page() || is_feed()) {
			$parser->fn_id_prefix = "";
		} else {
			$parser->fn_id_prefix = get_the_ID() . ".";
		}
		return $parser->transform($text);
	}
	
	# Comments
	# - Remove WordPress paragraph generator.
	# - Remove WordPress auto-link generator.
	# - Scramble important tags before passing them to the kses filter.
	# - Run Markdown on excerpt then remove paragraph tags.
	if (MARKDOWN_WP_COMMENTS) {
		remove_filter('comment_text', 'wpautop', 30);
		remove_filter('comment_text', 'make_clickable');
		add_filter('pre_comment_content', 'Markdown', 6);
		add_filter('pre_comment_content', 'mdwp_hide_tags', 8);
		add_filter('pre_comment_content', 'mdwp_show_tags', 12);
		add_filter('get_comment_text',    'Markdown', 6);
		add_filter('get_comment_excerpt', 'Markdown', 6);
		add_filter('get_comment_excerpt', 'mdwp_strip_p', 7);
	
		global $mdwp_hidden_tags, $mdwp_placeholders;
		$mdwp_hidden_tags = explode(' ',
			'<p> </p> <pre> </pre> <ol> </ol> <ul> </ul> <li> </li>');
		$mdwp_placeholders = explode(' ', str_rot13(
			'pEj07ZbbBZ U1kqgh4w4p pre2zmeN6K QTi31t9pre ol0MP1jzJR '.
			'ML5IjmbRol ulANi1NsGY J7zRLJqPul liA8ctl16T K9nhooUHli'));
	}
	
	function mdwp_add_p($text) {
		if (!preg_match('{^$|^<(p|ul|ol|dl|pre|blockquote)>}i', $text)) {
			$text = '<p>'.$text.'</p>';
			$text = preg_replace('{\n{2,}}', "</p>\n\n<p>", $text);
		}
		return $text;
	}
	
	function mdwp_strip_p($t) { return preg_replace('{</?p>}i', '', $t); }

	function mdwp_hide_tags($text) {
		global $mdwp_hidden_tags, $mdwp_placeholders;
		return str_replace($mdwp_hidden_tags, $mdwp_placeholders, $text);
	}
	function mdwp_show_tags($text) {
		global $mdwp_hidden_tags, $mdwp_placeholders;
		return str_replace($mdwp_placeholders, $mdwp_hidden_tags, $text);
	}
}


### bBlog Plugin Info ###

function identify_modifier_markdown() {
	return array(
		'name' => 'markdown',
		'type' => 'modifier',
		'nicename' => 'PHP Markdown Extra',
		'description' => 'A text-to-HTML conversion tool for web writers',
		'authors' => 'Michel Fortin and John Gruber',
		'licence' => 'GPL',
		'version' => MARKDOWNEXTRA_VERSION,
		'help' => '<a href="http://daringfireball.net/projects/markdown/syntax">Markdown syntax</a> allows you to write using an easy-to-read, easy-to-write plain text format. Based on the original Perl version by <a href="http://daringfireball.net/">John Gruber</a>. <a href="http://michelf.com/projects/php-markdown/">More...</a>',
		);
}


### Smarty Modifier Interface ###

function smarty_modifier_markdown($text) {
	return Markdown($text);
}


### Textile Compatibility Mode ###

# Rename this file to "classTextile.php" and it can replace Textile everywhere.

if (strcasecmp(substr(__FILE__, -16), "classTextile.php") == 0) {
	# Try to include PHP SmartyPants. Should be in the same directory.
	@include_once 'smartypants.php';
	# Fake Textile class. It calls Markdown instead.
	class Textile {
		function TextileThis($text, $lite='', $encode='') {
			if ($lite == '' && $encode == '')    $text = Markdown($text);
			if (function_exists('SmartyPants'))  $text = SmartyPants($text);
			return $text;
		}
		# Fake restricted version: restrictions are not supported for now.
		function TextileRestricted($text, $lite='', $noimage='') {
			return $this->TextileThis($text, $lite);
		}
		# Workaround to ensure compatibility with TextPattern 4.0.3.
		function blockLite($text) { return $text; }
	}
}



#
# Markdown Parser Class
#

class Markdown_Parser {

	# Regex to match balanced [brackets].
	# Needed to insert a maximum bracked depth while converting to PHP.
	var $nested_brackets_depth = 6;
	var $nested_brackets_re;
	
	var $nested_url_parenthesis_depth = 4;
	var $nested_url_parenthesis_re;

	# Table of hash values for escaped characters:
	var $escape_chars = '\`*_{}[]()>#+-.!';
	var $escape_chars_re;

	# Change to ">" for HTML output.
	var $empty_element_suffix = MARKDOWN_EMPTY_ELEMENT_SUFFIX;
	var $tab_width = MARKDOWN_TAB_WIDTH;
	
	# Change to `true` to disallow markup or entities.
	var $no_markup = false;
	var $no_entities = false;
	
	# Predefined urls and titles for reference links and images.
	var $predef_urls = array();
	var $predef_titles = array();


	function Markdown_Parser() {
	#
	# Constructor function. Initialize appropriate member variables.
	#
		$this->_initDetab();
		$this->prepareItalicsAndBold();
	
		$this->nested_brackets_re = 
			str_repeat('(?>[^\[\]]+|\[', $this->nested_brackets_depth).
			str_repeat('\])*', $this->nested_brackets_depth);
	
		$this->nested_url_parenthesis_re = 
			str_repeat('(?>[^()\s]+|\(', $this->nested_url_parenthesis_depth).
			str_repeat('(?>\)))*', $this->nested_url_parenthesis_depth);
		
		$this->escape_chars_re = '['.preg_quote($this->escape_chars).']';
		
		# Sort document, block, and span gamut in ascendent priority order.
		asort($this->document_gamut);
		asort($this->block_gamut);
		asort($this->span_gamut);
	}


	# Internal hashes used during transformation.
	var $urls = array();
	var $links = array();
	var $titles = array();
	var $html_hashes = array();
	
	# Status flag to avoid invalid nesting.
	var $in_anchor = false;
	
	
	function setup() {
	#
	# Called before the transformation process starts to setup parser 
	# states.
	#
		# Clear global hashes.
		$this->urls = $this->predef_urls;
		$this->links = array();
		$this->titles = $this->predef_titles;
		$this->html_hashes = array();
		
		$in_anchor = false;
	}
	
	function teardown() {
	#
	# Called after the transformation process to clear any variable 
	# which may be taking up memory unnecessarly.
	#
		$this->urls = array();
		$this->links = array();
		$this->titles = array();
		$this->html_hashes = array();
	}


	function transform($text) {
	#
	# Main function. Performs some preprocessing on the input text
	# and pass it through the document gamut.
	#
		$this->setup();
	
		# Remove UTF-8 BOM and marker character in input, if present.
		$text = preg_replace('{^\xEF\xBB\xBF|\x1A}', '', $text);

		# Standardize line endings:
		#   DOS to Unix and Mac to Unix
		$text = preg_replace('{\r\n?}', "\n", $text);

		# Make sure $text ends with a couple of newlines:
		$text = "\n\n" . $text . "\n\n";

		# Convert all tabs to spaces.
		$text = $this->detab($text);

		# Turn block-level HTML blocks into hash entries
		$text = $this->hashHTMLBlocks($text, '_hashHTMLBlocks_callback2');

		# Strip any lines consisting only of spaces and tabs.
		# This makes subsequent regexen easier to write, because we can
		# match consecutive blank lines with /\n+/ instead of something
		# contorted like /[ ]*\n+/ .
		$text = preg_replace('/^[ ]+$/m', '', $text);

		# Run document gamut methods.
		foreach ($this->document_gamut as $method => $priority) {
			$text = $this->$method($text);
		}

		$text = preg_replace('~&#x([0-9a-f]+);~ei', 'chr(hexdec("\\1"))', $text);
		$text = preg_replace('~&#([0-9]+);~e', 'chr("\\1")', $text); 
		
		$this->teardown();

		return $text;
	}
	
	var $document_gamut = array(
		# Strip link definitions, store in hashes.
		"stripLinkDefinitions" => 20,
		
		"runBasicBlockGamut"   => 30,
		);


	function stripLinkDefinitions($text) {
	#
	# Strips link definitions from text, stores the URLs and titles in
	# hash references.
	#
		$less_than_tab = $this->tab_width - 1;

		# Link defs are in the form: ^[id]: url "optional title"
		$text = preg_replace_callback('{
							^[ ]{0,'.$less_than_tab.'}\[(.+)\][ ]?:	# id = $1
							  [ ]*
							  \n?				# maybe *one* newline
							  [ ]*
							(?:
							  <(.+?)>			# url = $2
							|
							  (\S+?)			# url = $3
							)
							  [ ]*
							  \n?				# maybe one newline
							  [ ]*
							(?:
								(?<=\s)			# lookbehind for whitespace
								["(]
								(.*?)			# title = $4
								[")]
								[ ]*
							)?	# title is optional
							(?:\n+|\Z)
			}xm',
			array(&$this, '_stripLinkDefinitions_callback'),
			$text);
		return $text;
	}
	function _stripLinkDefinitions_callback($matches) {
		$link_id = strtolower($matches[1]);
		$url = $matches[2] == '' ? $matches[3] : $matches[2];
		$this->urls[$link_id] = $url;
		$this->titles[$link_id] =& $matches[4];
		return ''; # String that will replace the block
	}


	function hashHTMLBlocks($text, $callback = '_hashHTMLBlocks_callback') {
		if ($this->no_markup)  return $text;

		$less_than_tab = $this->tab_width - 1;

		# Hashify HTML blocks:
		# We only want to do this for block-level HTML tags, such as headers,
		# lists, and tables. That's because we still want to wrap <p>s around
		# "paragraphs" that are wrapped in non-block-level tags, such as anchors,
		# phrase emphasis, and spans. The list of tags we're looking for is
		# hard-coded:
		#
		# *  List "a" is made of tags which can be both inline or block-level.
		#    These will be treated block-level when the start tag is alone on 
		#    its line, otherwise they're not matched here and will be taken as 
		#    inline later.
		# *  List "b" is made of tags which are always block-level;
		#
		$block_tags_a_re = 'ins|del';
		$block_tags_b_re = 'p|div|h[1-6]|blockquote|pre|table|dl|ol|ul|script|noscript|form|fieldset|iframe|math|style|section|header|footer|nav|article|aside';

		# Regular expression for the content of a block tag.
		$nested_tags_level = 4;
		$attr = '
			(?>				# optional tag attributes
			  \s			# starts with whitespace
			  (?>
				[^>"/]+		# text outside quotes
			  |
				/+(?!>)		# slash not followed by ">"
			  |
				"[^"]*"		# text inside double quotes (tolerate ">")
			  |
				\'[^\']*\'	# text inside single quotes (tolerate ">")
			  )*
			)?
			';
		$content =
			str_repeat('
				(?>
				  [^<]+			# content without tag
				|
				  <\2			# nested opening tag
					'.$attr.'	# attributes
					(?>
					  />
					|
					  >', $nested_tags_level).	# end of opening tag
					  '.*?'.					# last level nested tag content
			str_repeat('
					  </\2\s*>	# closing nested tag
					)
				  |
					<(?!/\2\s*>	# other tags with a different name
				  )
				)*',
				$nested_tags_level);
		$content2 = str_replace('\2', '\3', $content);

		# First, look for nested blocks, e.g.:
		# 	<div>
		# 		<div>
		# 		tags for inner block must be indented.
		# 		</div>
		# 	</div>
		#
		# The outermost tags must start at the left margin for this to match, and
		# the inner nested divs must be indented.
		# We need to do this before the next, more liberal match, because the next
		# match will start at the first `<div>` and stop at the first `</div>`.
		$text = preg_replace_callback('{(?>
			(?>
				(?<=\n)		# Starting after a blank line
				|				# or
				\A\n?			# the beginning of the doc
			)
			(						# save in $1

			  # Match from `\n<tag>` to `</tag>\n`, handling nested tags 
			  # in between.
					
						[ ]{0,'.$less_than_tab.'}
						<('.$block_tags_b_re.')# start tag = $2
						'.$attr.'>			# attributes followed by > and \n
						'.$content.'		# content, support nesting
						</\2>				# the matching end tag
						[ ]*				# trailing spaces/tabs
						(?=\n+|\Z)	# followed by a newline or end of document

			| # Special version for tags of group a.

						[ ]{0,'.$less_than_tab.'}
						<('.$block_tags_a_re.')# start tag = $3
						'.$attr.'>[ ]*\n	# attributes followed by >
						'.$content2.'		# content, support nesting
						</\3>				# the matching end tag
						[ ]*				# trailing spaces/tabs
						(?=\n+|\Z)	# followed by a newline or end of document
					
			| # Special case just for <hr />. It was easier to make a special 
			  # case than to make the other regex more complicated.
			
						[ ]{0,'.$less_than_tab.'}
						<(hr)				# start tag = $2
						'.$attr.'			# attributes
						/?>					# the matching end tag
						[ ]*
						(?=\n{2,}|\Z)		# followed by a blank line or end of document
			
			| # Special case for standalone HTML comments:
			
					[ ]{0,'.$less_than_tab.'}
					(?s:
						<!-- .*? -->
					)
					[ ]*
					(?=\n{2,}|\Z)		# followed by a blank line or end of document
			
			| # PHP and ASP-style processor instructions (<? and <%)
			
					[ ]{0,'.$less_than_tab.'}
					(?s:
						<([?%])			# $2
						.*?
						\2>
					)
					[ ]*
					(?=\n{2,}|\Z)		# followed by a blank line or end of document
					
			)
			)}Sxmi',
			array(&$this, $callback),
			$text);

		return $text;
	}
	function _hashHTMLBlocks_callback($matches) {
		$text = $matches[1];
		$key  = $this->hashBlock($text);
		return "\n\n$key\n\n";
	}
	function _hashHTMLBlocks_callback2($matches) {
		$text = $matches[1];
		$key  = $this->hashPart($text, 'J');
		return "\n\n$key\n\n";
	}
	
	
	function hashPart($text, $boundary = 'X') {
	#
	# Called whenever a tag must be hashed when a function insert an atomic 
	# element in the text stream. Passing $text to through this function gives
	# a unique text-token which will be reverted back when calling unhash.
	#
	# The $boundary argument specify what character should be used to surround
	# the token. By convension, "B" is used for block elements that needs not
	# to be wrapped into paragraph tags at the end, ":" is used for elements
	# that are word separators and "X" is used in the general case.
	#
		# Swap back any tag hash found in $text so we do not have to `unhash`
		# multiple times at the end.
		$text = $this->unhash($text);
		
		# Then hash the block.
		static $i = 0;
		$key = "$boundary\x1A" . ++$i . $boundary;
		$this->html_hashes[$key] = $text;
		return $key; # String that will replace the tag.
	}


	function hashBlock($text) {
	#
	# Shortcut function for hashPart with block-level boundaries.
	#
		return $this->hashPart($text, 'B');
	}


	var $block_gamut = array(
	#
	# These are all the transformations that form block-level
	# tags like paragraphs, headers, and list items.
	#
		"doHeaders"         => 10,
		"doHorizontalRules" => 20,
		
		"doLists"           => 40,
		"doCodeBlocks"      => 50,
		"doBlockQuotes"     => 60,
		);

	function runBlockGamut($text) {
	#
	# Run block gamut tranformations.
	#
		# We need to escape raw HTML in Markdown source before doing anything 
		# else. This need to be done for each block, and not only at the 
		# begining in the Markdown function since hashed blocks can be part of
		# list items and could have been indented. Indented blocks would have 
		# been seen as a code block in a previous pass of hashHTMLBlocks.
		$text = $this->hashHTMLBlocks($text);
		
		return $this->runBasicBlockGamut($text);
	}
	
	function runBasicBlockGamut($text) {
	#
	# Run block gamut tranformations, without hashing HTML blocks. This is 
	# useful when HTML blocks are known to be already hashed, like in the first
	# whole-document pass.
	#
		foreach ($this->block_gamut as $method => $priority) {
			$text = $this->$method($text);
		}
		
		# Finally form paragraph and restore hashed blocks.
		$text = $this->formParagraphs($text);

		return $text;
	}
	
	
	function doHorizontalRules($text) {
		# Do Horizontal Rules:
		return preg_replace(
			'{
				^[ ]{0,3}	# Leading space
				([-*_])		# $1: First marker
				(?>			# Repeated marker group
					[ ]{0,2}	# Zero, one, or two spaces.
					\1			# Marker character
				){2,}		# Group repeated at least twice
				[ ]*		# Tailing spaces
				$			# End of line.
			}mx',
			"\n".$this->hashBlock("<hr$this->empty_element_suffix")."\n", 
			$text);
	}


	var $span_gamut = array(
	#
	# These are all the transformations that occur *within* block-level
	# tags like paragraphs, headers, and list items.
	#
		# Process character escapes, code spans, and inline HTML
		# in one shot.
		"parseSpan"           => -30,

		# Process anchor and image tags. Images must come first,
		# because ![foo][f] looks like an anchor.
		"doImages"            =>  10,
		"doAnchors"           =>  20,
		
		# Make links out of things like `<http://example.com/>`
		# Must come after doAnchors, because you can use < and >
		# delimiters in inline links like [this](<url>).
		"doAutoLinks"         =>  30,
		"encodeAmpsAndAngles" =>  40,

		"doItalicsAndBold"    =>  50,
		"doHardBreaks"        =>  60,

		"doUnhashLinks"       =>  70,
		);

	function runSpanGamut($text) {
	#
	# Run span gamut tranformations.
	#
		foreach ($this->span_gamut as $method => $priority) {
			$text = $this->$method($text);
		}

		return $text;
	}
	
	
	function doHardBreaks($text) {
		# Do hard breaks:
		return preg_replace_callback('/ {2,}\n/', 
			array(&$this, '_doHardBreaks_callback'), $text);
	}
	function _doHardBreaks_callback($matches) {
		return $this->hashPart("<br$this->empty_element_suffix\n");
	}

	function doUnhashLinks($text) {
		$text = preg_replace_callback('/(~L(\d+)L)/', array(&$this, '_doUnhashLinks_callback'), $text);
		return $text;
	}
	function _doUnhashLinks_callback($matches) {
		return $this->links[$matches[2]];
	}

	function doAnchors($text) {
	#
	# Turn Markdown link shortcuts into XHTML <a> tags.
	#
		if ($this->in_anchor) return $text;
		$this->in_anchor = true;
		
		#
		# First, handle reference-style links: [link text] [id]
		#
		$text = preg_replace_callback('{
			(					# wrap whole match in $1
			  \[
				('.$this->nested_brackets_re.')	# link text = $2
			  \]

			  [ ]?				# one optional space
			  (?:\n[ ]*)?		# one optional newline followed by spaces

			  \[
				(.*?)		# id = $3
			  \]
			)
			}xs',
			array(&$this, '_doAnchors_reference_callback'), $text);

		#
		# Next, inline-style links: [link text](url "optional title")
		#
		$text = preg_replace_callback('{
			(				# wrap whole match in $1
			  \[
				('.$this->nested_brackets_re.')	# link text = $2
			  \]
			  \(			# literal paren
				[ \n]*
				(?:
					<(.+?)>	# href = $3
				|
					('.$this->nested_url_parenthesis_re.')	# href = $4
				)
				[ \n]*
				(			# $5
				  ([\'"])	# quote char = $6
				  (.*?)		# Title = $7
				  \6		# matching quote
				  [ \n]*	# ignore any spaces/tabs between closing quote and )
				)?			# title is optional
			  \)
			)
			}xs',
			array(&$this, '_doAnchors_inline_callback'), $text);

		#
		# Last, handle reference-style shortcuts: [link text]
		# These must come last in case you've also got [link text][1]
		# or [link text](/foo)
		#
		$text = preg_replace_callback('{
			(					# wrap whole match in $1
			  \[
				([^\[\]]+)		# link text = $2; can\'t contain [ or ]
			  \]
			)
			}xs',
			array(&$this, '_doAnchors_reference_callback'), $text);

		$this->in_anchor = false;
		return $text;
	}
	function _doAnchors_reference_callback($matches) {
		$whole_match =  $matches[1];
		$link_text   =  $matches[2];
		$link_id     =& $matches[3];

		if ($link_id == "") {
			# for shortcut links like [this][] or [this].
			$link_id = $link_text;
		}
		
		# lower-case and turn embedded newlines into spaces
		$link_id = strtolower($link_id);
		$link_id = preg_replace('{[ ]?\n}', ' ', $link_id);

		if (isset($this->urls[$link_id])) {
			$url = $this->urls[$link_id];
			$url = $this->encodeAttribute($url);
			
			$result = "<a href=\"$url\"";
			if ( isset( $this->titles[$link_id] ) ) {
				$title = $this->titles[$link_id];
				$title = $this->encodeAttribute($title);
				$result .=  " title=\"$title\"";
			}
		
			$link_text = $this->runSpanGamut($link_text);
			$result .= ">$link_text</a>";
			$result = $this->hashPart($result);
		}
		else {
			$result = $whole_match;
		}

		return "~L" . (array_push($this->links, $result) - 1) . "L";
	}
	function _doAnchors_inline_callback($matches) {
		$whole_match	=  $matches[1];
		$link_text		=  $this->runSpanGamut($matches[2]);
		$url			=  $matches[3] == '' ? $matches[4] : $matches[3];
		$title			=& $matches[7];

		$url = $this->encodeAttribute($url);

		$result = "<a href=\"$url\"";
		if (isset($title)) {
			$title = $this->encodeAttribute($title);
			$result .=  " title=\"$title\"";
		}
		
		$link_text = $this->runSpanGamut($link_text);
		$result .= ">$link_text</a>";

		return "~L" . (array_push($this->links, $result) - 1) . "L";
	}


	function doImages($text) {
	#
	# Turn Markdown image shortcuts into <img> tags.
	#
		#
		# First, handle reference-style labeled images: ![alt text][id]
		#
		$text = preg_replace_callback('{
			(				# wrap whole match in $1
			  !\[
				('.$this->nested_brackets_re.')		# alt text = $2
			  \]

			  [ ]?				# one optional space
			  (?:\n[ ]*)?		# one optional newline followed by spaces

			  \[
				(.*?)		# id = $3
			  \]

			)
			}xs', 
			array(&$this, '_doImages_reference_callback'), $text);

		#
		# Next, handle inline images:  ![alt text](url "optional title")
		# Don't forget: encode * and _
		#
		$text = preg_replace_callback('{
			(				# wrap whole match in $1
			  !\[
				('.$this->nested_brackets_re.')		# alt text = $2
			  \]
			  \s?			# One optional whitespace character
			  \(			# literal paren
				[ \n]*
				(?:
					<(\S*)>	# src url = $3
				|
					('.$this->nested_url_parenthesis_re.')	# src url = $4
				)
				[ \n]*
				(			# $5
				  ([\'"])	# quote char = $6
				  (.*?)		# title = $7
				  \6		# matching quote
				  [ \n]*
				)?			# title is optional
			  \)
			)
			}xs',
			array(&$this, '_doImages_inline_callback'), $text);

		return $text;
	}
	function _doImages_reference_callback($matches) {
		$whole_match = $matches[1];
		$alt_text    = $matches[2];
		$link_id     = strtolower($matches[3]);

		if ($link_id == "") {
			$link_id = strtolower($alt_text); # for shortcut links like ![this][].
		}

		$alt_text = $this->encodeAttribute($alt_text);
		if (isset($this->urls[$link_id])) {
			$url = $this->encodeAttribute($this->urls[$link_id]);
			$result = "<img src=\"$url\" alt=\"$alt_text\"";
			if (isset($this->titles[$link_id])) {
				$title = $this->titles[$link_id];
				$title = $this->encodeAttribute($title);
				$result .=  " title=\"$title\"";
			} else {
				$result .=  " title=\"\"";
			}
			$result .= $this->empty_element_suffix;
			$result = $this->hashPart($result);
		}
		else {
			# If there's no such link ID, leave intact:
			$result = $whole_match;
		}

		return "~L" . (array_push($this->links, $result) - 1) . "L";
	}
	function _doImages_inline_callback($matches) {
		$whole_match	= $matches[1];
		$alt_text		= $matches[2];
		$url			= $matches[3] == '' ? $matches[4] : $matches[3];
		$title			=& $matches[7];

		$alt_text = $this->encodeAttribute($alt_text);
		$url = $this->encodeAttribute($url);
		$result = "<img src=\"$url\" alt=\"$alt_text\"";
		if (isset($title)) {
			$title = $this->encodeAttribute($title);
			$result .=  " title=\"$title\""; # $title already quoted
		} else {
			$result .=  " title=\"\"";
		}
		$result .= $this->empty_element_suffix;

		return "~L" . (array_push($this->links, $result) - 1) . "L";
	}


	function doHeaders($text) {
		# Setext-style headers:
		#	  Header 1
		#	  ========
		#  
		#	  Header 2
		#	  --------
		#
		$text = preg_replace_callback('{ ^(.+?)[ ]*\n(=+|-+)[ ]*\n+ }mx',
			array(&$this, '_doHeaders_callback_setext'), $text);

		# atx-style headers:
		#	# Header 1
		#	## Header 2
		#	## Header 2 with closing hashes ##
		#	...
		#	###### Header 6
		#
		$text = preg_replace_callback('{
				^(\#{1,6})	# $1 = string of #\'s
				[ ]*
				(.+?)		# $2 = Header text
				[ ]*
				\#*			# optional closing #\'s (not counted)
				\n+
			}xm',
			array(&$this, '_doHeaders_callback_atx'), $text);

		return $text;
	}
	function _doHeaders_callback_setext($matches) {
		# Terrible hack to check we haven't found an empty list item.
		if ($matches[2] == '-' && preg_match('{^-(?: |$)}', $matches[1]))
			return $matches[0];
		
		$level = $matches[2]{0} == '=' ? 1 : 2;
		$block = "<h$level>".$this->runSpanGamut($matches[1])."</h$level>";
		return "\n" . $this->hashBlock($block) . "\n\n";
	}
	function _doHeaders_callback_atx($matches) {
		$level = strlen($matches[1]);
		$block = "<h$level>".$this->runSpanGamut($matches[2])."</h$level>";
		return "\n" . $this->hashBlock($block) . "\n\n";
	}


	function doLists($text) {
	#
	# Form HTML ordered (numbered) and unordered (bulleted) lists.
	#
		$less_than_tab = $this->tab_width - 1;

		# Re-usable patterns to match list item bullets and number markers:
		$marker_ul_re  = '[*+-]';
		$marker_ol_re  = '\d+[\.]';
		$marker_any_re = "(?:$marker_ul_re|$marker_ol_re)";

		$markers_relist = array(
			$marker_ul_re => $marker_ol_re,
			$marker_ol_re => $marker_ul_re,
			);

		foreach ($markers_relist as $marker_re => $other_marker_re) {
			# Re-usable pattern to match any entirel ul or ol list:
			$whole_list_re = '
				(								# $1 = whole list
				  (								# $2
					([ ]{0,'.$less_than_tab.'})	# $3 = number of spaces
					('.$marker_re.')			# $4 = first list item marker
					[ ]+
				  )
				  (?s:.+?)
				  (								# $5
					  \z
					|
					  \n{2,}
					  (?=\S)
					  (?!						# Negative lookahead for another list item marker
						[ ]*
						'.$marker_re.'[ ]+
					  )
					|
					  (?=						# Lookahead for another kind of list
					    \n
						\3						# Must have the same indentation
						'.$other_marker_re.'[ ]+
					  )
				  )
				)
			'; // mx
			
			# We use a different prefix before nested lists than top-level lists.
			# See extended comment in _ProcessListItems().
		
			if ($this->list_level) {
				$text = preg_replace_callback('{
						^
						'.$whole_list_re.'
					}mx',
					array(&$this, '_doLists_callback'), $text);
			}
			else {
				$text = preg_replace_callback('{
						(?:(?<=\n)\n|\A\n?) # Must eat the newline
						'.$whole_list_re.'
					}mx',
					array(&$this, '_doLists_callback'), $text);
			}
		}

		return $text;
	}
	function _doLists_callback($matches) {
		# Re-usable patterns to match list item bullets and number markers:
		$marker_ul_re  = '[*+-]';
		$marker_ol_re  = '\d+[\.]';
		$marker_any_re = "(?:$marker_ul_re|$marker_ol_re)";
		
		$list = $matches[1];
		$list_type = preg_match("/$marker_ul_re/", $matches[4]) ? "ul" : "ol";
		
		$marker_any_re = ( $list_type == "ul" ? $marker_ul_re : $marker_ol_re );
		
		$list .= "\n";
		$result = $this->processListItems($list, $marker_any_re);
		
		$result = $this->hashBlock("<$list_type>\n" . $result . "</$list_type>");
		return "\n". $result ."\n\n";
	}

	var $list_level = 0;

	function processListItems($list_str, $marker_any_re) {
	#
	#	Process the contents of a single ordered or unordered list, splitting it
	#	into individual list items.
	#
		# The $this->list_level global keeps track of when we're inside a list.
		# Each time we enter a list, we increment it; when we leave a list,
		# we decrement. If it's zero, we're not in a list anymore.
		#
		# We do this because when we're not inside a list, we want to treat
		# something like this:
		#
		#		I recommend upgrading to version
		#		8. Oops, now this line is treated
		#		as a sub-list.
		#
		# As a single paragraph, despite the fact that the second line starts
		# with a digit-period-space sequence.
		#
		# Whereas when we're inside a list (or sub-list), that line will be
		# treated as the start of a sub-list. What a kludge, huh? This is
		# an aspect of Markdown's syntax that's hard to parse perfectly
		# without resorting to mind-reading. Perhaps the solution is to
		# change the syntax rules such that sub-lists must start with a
		# starting cardinal number; e.g. "1." or "a.".
		
		$this->list_level++;

		# trim trailing blank lines:
		$list_str = preg_replace("/\n{2,}\\z/", "\n", $list_str);

		$list_str = preg_replace_callback('{
			(\n)?							# leading line = $1
			(^[ ]*)							# leading whitespace = $2
			('.$marker_any_re.'				# list marker and space = $3
				(?:[ ]+|(?=\n))	# space only required if item is not empty
			)
			((?s:.*?))						# list item text   = $4
			(?:(\n+(?=\n))|\n)				# tailing blank line = $5
			(?= \n* (\z | \2 ('.$marker_any_re.') (?:[ ]+|(?=\n))))
			}xm',
			array(&$this, '_processListItems_callback'), $list_str);

		$this->list_level--;
		return $list_str;
	}
	function _processListItems_callback($matches) {
		$item = $matches[4];
		$leading_line =& $matches[1];
		$leading_space =& $matches[2];
		$marker_space = $matches[3];
		$tailing_blank_line =& $matches[5];

		if ($leading_line || $tailing_blank_line || 
			preg_match('/\n{2,}/', $item))
		{
			# Replace marker with the appropriate whitespace indentation
			$item = $leading_space . str_repeat(' ', strlen($marker_space)) . $item;
			$item = $this->runBlockGamut($this->outdent($item)."\n");
		}
		else {
			# Recursion for sub-lists:
			$item = $this->doLists($this->outdent($item));
			$item = preg_replace('/\n+$/', '', $item);
			$item = $this->runSpanGamut($item);
		}

		return "    " . "<li>" . $item . "</li>\n";
	}


	function doCodeBlocks($text) {
	#
	#	Process Markdown `<pre><code>` blocks.
	#
		$text = preg_replace_callback('{
				(?:\n\n|\A\n?)
				(	            # $1 = the code block -- one or more lines, starting with a space/tab
				  (?>
					[ ]{'.$this->tab_width.'}  # Lines must start with a tab or a tab-width of spaces
					.*\n+
				  )+
				)
				((?=^[ ]{0,'.$this->tab_width.'}\S)|\Z)	# Lookahead for non-space at line-start, or end of doc
			}xm',
			array(&$this, '_doCodeBlocks_callback'), $text);

		return $text;
	}
	function _doCodeBlocks_callback($matches) {
		$codeblock = $matches[1];

		$codeblock = $this->outdent($codeblock);
		$codeblock = htmlspecialchars($codeblock, ENT_NOQUOTES);

		# trim leading newlines and trailing newlines
		$codeblock = preg_replace('/\A\n+|\n+\z/', '', $codeblock);

		$codeblock = "<pre><code>$codeblock\n</code></pre>";
		return "\n\n".$this->hashBlock($codeblock)."\n\n";
	}


	function makeCodeSpan($code) {
	#
	# Create a code span markup for $code. Called from handleSpanToken.
	#
		$code = htmlspecialchars(trim($code), ENT_NOQUOTES);
		return $this->hashPart("<code>$code</code>");
	}


	var $em_relist = array(
		''  => '(?:(?<!\*)\*(?!\*)|(?<!_)_(?!_))(?=\S|$)(?![\.,:;]\s)',
		'*' => '(?<=\S|^)(?<!\*)\*(?!\*)',
		'_' => '(?<=\S|^)(?<!_)_(?!_)',
		);
	var $strong_relist = array(
		''   => '(?:(?<!\*)\*\*(?!\*)|(?<!_)__(?!_))(?=\S|$)(?![\.,:;]\s)',
		'**' => '(?<=\S|^)(?<!\*)\*\*(?!\*)',
		'__' => '(?<=\S|^)(?<!_)__(?!_)',
		);
	var $em_strong_relist = array(
		''    => '(?:(?<!\*)\*\*\*(?!\*)|(?<!_)___(?!_))(?=\S|$)(?![\.,:;]\s)',
		'***' => '(?<=\S|^)(?<!\*)\*\*\*(?!\*)',
		'___' => '(?<=\S|^)(?<!_)___(?!_)',
		);
	var $em_strong_prepared_relist;
	
	function prepareItalicsAndBold() {
	#
	# Prepare regular expressions for searching emphasis tokens in any
	# context.
	#
		foreach ($this->em_relist as $em => $em_re) {
			foreach ($this->strong_relist as $strong => $strong_re) {
				# Construct list of allowed token expressions.
				$token_relist = array();
				if (isset($this->em_strong_relist["$em$strong"])) {
					$token_relist[] = $this->em_strong_relist["$em$strong"];
				}
				$token_relist[] = $em_re;
				$token_relist[] = $strong_re;
				
				# Construct master expression from list.
				$token_re = '{('. implode('|', $token_relist) .')}';
				$this->em_strong_prepared_relist["$em$strong"] = $token_re;
			}
		}
	}
	
	function doItalicsAndBold($text) {
		$token_stack = array('');
		$text_stack = array('');
		$em = '';
		$strong = '';
		$tree_char_em = false;
		
		while (1) {
			#
			# Get prepared regular expression for seraching emphasis tokens
			# in current context.
			#
			$token_re = $this->em_strong_prepared_relist["$em$strong"];
			
			#
			# Each loop iteration search for the next emphasis token. 
			# Each token is then passed to handleSpanToken.
			#
			$parts = preg_split($token_re, $text, 2, PREG_SPLIT_DELIM_CAPTURE);
			$text_stack[0] .= $parts[0];
			$token =& $parts[1];
			$text =& $parts[2];
			
			if (empty($token)) {
				# Reached end of text span: empty stack without emitting.
				# any more emphasis.
				while ($token_stack[0]) {
					$text_stack[1] .= array_shift($token_stack);
					$text_stack[0] .= array_shift($text_stack);
				}
				break;
			}
			
			$token_len = strlen($token);
			if ($tree_char_em) {
				# Reached closing marker while inside a three-char emphasis.
				if ($token_len == 3) {
					# Three-char closing marker, close em and strong.
					array_shift($token_stack);
					$span = array_shift($text_stack);
					$span = $this->runSpanGamut($span);
					$span = "<strong><em>$span</em></strong>";
					$text_stack[0] .= $this->hashPart($span);
					$em = '';
					$strong = '';
				} else {
					# Other closing marker: close one em or strong and
					# change current token state to match the other
					$token_stack[0] = str_repeat($token{0}, 3-$token_len);
					$tag = $token_len == 2 ? "strong" : "em";
					$span = $text_stack[0];
					$span = $this->runSpanGamut($span);
					$span = "<$tag>$span</$tag>";
					$text_stack[0] = $this->hashPart($span);
					$$tag = ''; # $$tag stands for $em or $strong
				}
				$tree_char_em = false;
			} else if ($token_len == 3) {
				if ($em) {
					# Reached closing marker for both em and strong.
					# Closing strong marker:
					for ($i = 0; $i < 2; ++$i) {
						$shifted_token = array_shift($token_stack);
						$tag = strlen($shifted_token) == 2 ? "strong" : "em";
						$span = array_shift($text_stack);
						$span = $this->runSpanGamut($span);
						$span = "<$tag>$span</$tag>";
						$text_stack[0] .= $this->hashPart($span);
						$$tag = ''; # $$tag stands for $em or $strong
					}
				} else {
					# Reached opening three-char emphasis marker. Push on token 
					# stack; will be handled by the special condition above.
					$em = $token{0};
					$strong = "$em$em";
					array_unshift($token_stack, $token);
					array_unshift($text_stack, '');
					$tree_char_em = true;
				}
			} else if ($token_len == 2) {
				if ($strong) {
					# Unwind any dangling emphasis marker:
					if (strlen($token_stack[0]) == 1) {
						$text_stack[1] .= array_shift($token_stack);
						$text_stack[0] .= array_shift($text_stack);
					}
					# Closing strong marker:
					array_shift($token_stack);
					$span = array_shift($text_stack);
					$span = $this->runSpanGamut($span);
					$span = "<strong>$span</strong>";
					$text_stack[0] .= $this->hashPart($span);
					$strong = '';
				} else {
					array_unshift($token_stack, $token);
					array_unshift($text_stack, '');
					$strong = $token;
				}
			} else {
				# Here $token_len == 1
				if ($em) {
					if (strlen($token_stack[0]) == 1) {
						# Closing emphasis marker:
						array_shift($token_stack);
						$span = array_shift($text_stack);
						$span = $this->runSpanGamut($span);
						$span = "<em>$span</em>";
						$text_stack[0] .= $this->hashPart($span);
						$em = '';
					} else {
						$text_stack[0] .= $token;
					}
				} else {
					array_unshift($token_stack, $token);
					array_unshift($text_stack, '');
					$em = $token;
				}
			}
		}
		return $text_stack[0];
	}


	function doBlockQuotes($text) {
		$text = preg_replace_callback('/
			  (								# Wrap whole match in $1
				(?>
				  ^[ ]*>[ ]?			# ">" at the start of a line
					.+\n					# rest of the first line
				  (.+\n)*					# subsequent consecutive lines
				  \n*						# blanks
				)+
			  )
			/xm',
			array(&$this, '_doBlockQuotes_callback'), $text);

		return $text;
	}
	function _doBlockQuotes_callback($matches) {
		$bq = $matches[1];
		# trim one level of quoting - trim whitespace-only lines
		$bq = preg_replace('/^[ ]*>[ ]?|^[ ]+$/m', '', $bq);
		$bq = $this->runBlockGamut($bq);		# recurse

		$bq = preg_replace('/^/m', "    ", $bq);
		$bq = preg_replace('/^[ ]+$/m', "", $bq);
		$bq = preg_replace('/\r\n?/', "", $bq);

		return "\n". $this->hashBlock("<blockquote>\n$bq\n</blockquote>")."\n\n";
	}

	function formParagraphs($text) {
	#
	#	Params:
	#		$text - string to process with html <p> tags
	#
		# Strip leading and trailing lines:
		$text = preg_replace('/\A\n+|\n+\z/', '', $text);

		$grafs = preg_split('/\n{2,}/', $text, -1, PREG_SPLIT_NO_EMPTY);

		#
		# Wrap <p> tags and unhashify HTML blocks
		#
		foreach ($grafs as $key => $value) {
			if (!preg_match('/^B\x1A[0-9]+B$/', $value)) {
				# Is a paragraph.
				$value = $this->runSpanGamut($value);
				$value = preg_replace('/^([ ]*)/', "<p>", $value);
				$value .= "</p>";
				$grafs[$key] = $this->unhash($value);
			}
			else {
				# Is a block.
				# Modify elements of @grafs in-place...
				$graf = $value;
				$block = $this->html_hashes[$graf];
				$graf = $block;
				$grafs[$key] = $graf;
			}
		}

		return implode("\n\n", $grafs);
	}


	function encodeAttribute($text) {
	#
	# Encode text for a double-quoted HTML attribute. This function
	# is *not* suitable for attributes enclosed in single quotes.
	#
		$text = $this->encodeAmpsAndAngles($text);
		$text = str_replace('"', '&quot;', $text);
		return $text;
	}
	
	
	function encodeAmpsAndAngles($text) {
	#
	# Smart processing for ampersands and angle brackets that need to 
	# be encoded. Valid character entities are left alone unless the
	# no-entities mode is set.
	#
		if ($this->no_entities) {
			$text = str_replace('&', '&amp;', $text);
		} else {
			# Ampersand-encoding based entirely on Nat Irons's Amputator
			# MT plugin: <http://bumppo.net/projects/amputator/>
			$text = preg_replace('/&(?!#?[xX]?(?:[0-9a-fA-F]+|\w+);)/', 
								'&amp;', $text);;
		}
		# Encode remaining <'s
		$text = str_replace('<', '&lt;', $text);

		return $text;
	}


	function doAutoLinks($text) {
		$text = preg_replace_callback("/<(((?:https?|ftp):\/\/?|www[.])(?:[-_.!~*'a-zA-Z0-9;\/?:\@&=+\$,%#]|(?:\([\w\d]+\)))+\/?)>/mi",
			array(&$this, '_doAutoLinks_url_callback'), $text);

		$text = preg_replace_callback("/(((?:https?|ftp):\/\/?|www[.])(?:[-_.!~*'a-zA-Z0-9;\/?:\@&=+\$,%#]|(?:\([\w\d]+\)))+\/?)/mi",
			array(&$this, '_doAutoLinks_url_callback'), $text);

		# Email addresses: <address@domain.foo>
		$text = preg_replace_callback('{
			<
			(?:mailto:)?
			(
				(?:
					[-!#$%&\'*+/=?^_`.{|}~\w\x80-\xFF]+
				|
					".*?"
				)
				\@
				(?:
					[-a-z0-9\x80-\xFF]+(\.[-a-z0-9\x80-\xFF]+)*\.[a-z]+
				|
					\[[\d.a-fA-F:]+\]	# IPv4 & IPv6
				)
			)
			>
			}xi',
			array(&$this, '_doAutoLinks_email_callback'), $text);

		return $text;
	}
	function _doAutoLinks_url_callback($matches) {
		$url = $this->encodeAttribute($matches[1]);
		$link = "<a href=\"$url\">$url</a>";
		return "~L" . (array_push($this->links, $link) - 1) . "L";
	}
	function _doAutoLinks_email_callback($matches) {
		$address = $matches[1];
		$link = $this->encodeEmailAddress($address);
		return "~L" . (array_push($this->links, $link) - 1) . "L";
	}


	function encodeEmailAddress($addr) {
	#
	#	Input: an email address, e.g. "foo@example.com"
	#
	#	Output: the email address as a mailto link, with each character
	#		of the address encoded as either a decimal or hex entity, in
	#		the hopes of foiling most address harvesting spam bots. E.g.:
	#
	#	  <p><a href="&#109;&#x61;&#105;&#x6c;&#116;&#x6f;&#58;&#x66;o&#111;
	#        &#x40;&#101;&#x78;&#97;&#x6d;&#112;&#x6c;&#101;&#46;&#x63;&#111;
	#        &#x6d;">&#x66;o&#111;&#x40;&#101;&#x78;&#97;&#x6d;&#112;&#x6c;
	#        &#101;&#46;&#x63;&#111;&#x6d;</a></p>
	#
	#	Based by a filter by Matthew Wickline, posted to BBEdit-Talk.
	#   With some optimizations by Milian Wolff.
	#
		$addr = "mailto:" . $addr;
		$chars = preg_split('/(?<!^)(?!$)/', $addr);
		$seed = (int)abs(crc32($addr) / strlen($addr)); # Deterministic seed.
		
		foreach ($chars as $key => $char) {
			$ord = ord($char);
			# Ignore non-ascii chars.
			if ($ord < 128) {
				$r = ($seed * (1 + $key)) % 100; # Pseudo-random function.
				# roughly 10% raw, 45% hex, 45% dec
				# '@' *must* be encoded. I insist.
				if ($r > 90 && $char != '@') /* do nothing */;
				else if ($r < 45) $chars[$key] = '&#x'.dechex($ord).';';
				else              $chars[$key] = '&#'.$ord.';';
			}
		}
		
		$addr = implode('', $chars);
		$text = implode('', array_slice($chars, 7)); # text without `mailto:`
		$addr = "<a href=\"$addr\">$text</a>";

		return $addr;
	}


	function parseSpan($str) {
	#
	# Take the string $str and parse it into tokens, hashing embeded HTML,
	# escaped characters and handling code spans.
	#
		$output = '';
		
		$span_re = '{
				(
					\\\\'.$this->escape_chars_re.'
				|
					(?<![`\\\\])
					`+						# code span marker
			'.( $this->no_markup ? '' : '
				|
					<!--    .*?     -->		# comment
				|
					<\?.*?\?> | <%.*?%>		# processing instruction
				|
					<[/!$]?[-a-zA-Z0-9:_]+	# regular tags
					(?>
						\s
						(?>[^"\'>]+|"[^"]*"|\'[^\']*\')*
					)?
					>
			').'
				)
				}xs';

		while (1) {
			#
			# Each loop iteration seach for either the next tag, the next 
			# openning code span marker, or the next escaped character. 
			# Each token is then passed to handleSpanToken.
			#
			$parts = preg_split($span_re, $str, 2, PREG_SPLIT_DELIM_CAPTURE);
			
			# Create token from text preceding tag.
			if ($parts[0] != "") {
				$output .= $parts[0];
			}
			
			# Check if we reach the end.
			if (isset($parts[1])) {
				$output .= $this->handleSpanToken($parts[1], $parts[2]);
				$str = $parts[2];
			}
			else {
				break;
			}
		}
		
		return $output;
	}
	
	
	function handleSpanToken($token, &$str) {
	#
	# Handle $token provided by parseSpan by determining its nature and 
	# returning the corresponding value that should replace it.
	#
		switch ($token{0}) {
			case "\\":
				return $this->hashPart("&#". ord($token{1}). ";");
			case "`":
				# Search for end marker in remaining text.
				if (preg_match('/^(.*?[^`])'.preg_quote($token).'(?!`)(.*)$/sm', 
					$str, $matches))
				{
					$str = $matches[2];
					$codespan = $this->makeCodeSpan($matches[1]);
					return $this->hashPart($codespan);
				}
				return $token; // return as text since no ending marker found.
			default:
				return $this->hashPart($token);
		}
	}


	function outdent($text) {
	#
	# Remove one level of line-leading tabs or spaces
	#
		return preg_replace('/^(\t|[ ]{1,'.$this->tab_width.'})/m', '', $text);
	}


	# String length function for detab. `_initDetab` will create a function to 
	# hanlde UTF-8 if the default function does not exist.
	var $utf8_strlen = 'mb_strlen';
	
	function detab($text) {
	#
	# Replace tabs with the appropriate amount of space.
	#
		# For each line we separate the line in blocks delemited by
		# tab characters. Then we reconstruct every line by adding the 
		# appropriate number of space between each blocks.
		
		$text = preg_replace_callback('/^.*\t.*$/m',
			array(&$this, '_detab_callback'), $text);

		return $text;
	}
	function _detab_callback($matches) {
		$line = $matches[0];
		$strlen = $this->utf8_strlen; # strlen function for UTF-8.
		
		# Split in blocks.
		$blocks = explode("\t", $line);
		# Add each blocks to the line.
		$line = $blocks[0];
		unset($blocks[0]); # Do not add first block twice.
		foreach ($blocks as $block) {
			# Calculate amount of space, insert spaces, insert block.
			$amount = $this->tab_width - 
				$strlen($line, 'UTF-8') % $this->tab_width;
			$line .= str_repeat(" ", $amount) . $block;
		}
		return $line;
	}
	function _initDetab() {
	#
	# Check for the availability of the function in the `utf8_strlen` property
	# (initially `mb_strlen`). If the function is not available, create a 
	# function that will loosely count the number of UTF-8 characters with a
	# regular expression.
	#
		if (function_exists($this->utf8_strlen)) return;
		$this->utf8_strlen = create_function('$text', 'return preg_match_all(
			"/[\\\\x00-\\\\xBF]|[\\\\xC0-\\\\xFF][\\\\x80-\\\\xBF]*/", 
			$text, $m);');
	}


	function unhash($text) {
	#
	# Swap back in all the tags hashed by _HashHTMLBlocks.
	#
		return preg_replace_callback('/(.)\x1A[0-9]+\1/', 
			array(&$this, '_unhash_callback'), $text);
	}
	function _unhash_callback($matches) {
		$unhash = $this->html_hashes[$matches[0]];

		if (preg_match('/J\x1A[0-9]+J/', $matches[0])) {
			return preg_replace('/<(\/?)([^>]+)>/m', '&lt;$1$2&gt;', $unhash);
		}

		return $unhash;
	}

}


#
# Markdown Extra Parser Class
#

class MarkdownExtra_Parser extends Markdown_Parser {

	# Prefix for footnote ids.
	var $fn_id_prefix = "";
	
	# Optional title attribute for footnote links and backlinks.
	var $fn_link_title = MARKDOWN_FN_LINK_TITLE;
	var $fn_backlink_title = MARKDOWN_FN_BACKLINK_TITLE;
	
	# Optional class attribute for footnote links and backlinks.
	var $fn_link_class = MARKDOWN_FN_LINK_CLASS;
	var $fn_backlink_class = MARKDOWN_FN_BACKLINK_CLASS;
	
	# Predefined abbreviations.
	var $predef_abbr = array();


	function MarkdownExtra_Parser() {
	#
	# Constructor function. Initialize the parser object.
	#
		# Add extra escapable characters before parent constructor 
		# initialize the table.
		$this->escape_chars .= ':|';
		
		# Insert extra document, block, and span transformations. 
		# Parent constructor will do the sorting.
		$this->document_gamut += array(
			"doFencedCodeBlocks" => 5,
			"stripFootnotes"     => 15,
			"stripAbbreviations" => 25,
			"appendFootnotes"    => 50,
			);
		$this->block_gamut += array(
			"doFencedCodeBlocks" => 5,
			"doTables"           => 15,
			"doDefLists"         => 45,
			);
		$this->span_gamut += array(
			"doFootnotes"        => 5,
			"doAbbreviations"    => 70,
			);
		
		parent::Markdown_Parser();
	}
	
	
	# Extra variables used during extra transformations.
	var $footnotes = array();
	var $footnotes_ordered = array();
	var $abbr_desciptions = array();
	var $abbr_word_re = '';
	
	# Give the current footnote number.
	var $footnote_counter = 1;
	
	
	function setup() {
	#
	# Setting up Extra-specific variables.
	#
		parent::setup();
		
		$this->footnotes = array();
		$this->footnotes_ordered = array();
		$this->abbr_desciptions = array();
		$this->abbr_word_re = '';
		$this->footnote_counter = 1;
		
		foreach ($this->predef_abbr as $abbr_word => $abbr_desc) {
			if ($this->abbr_word_re)
				$this->abbr_word_re .= '|';
			$this->abbr_word_re .= preg_quote($abbr_word);
			$this->abbr_desciptions[$abbr_word] = trim($abbr_desc);
		}
	}
	
	function teardown() {
	#
	# Clearing Extra-specific variables.
	#
		$this->footnotes = array();
		$this->footnotes_ordered = array();
		$this->abbr_desciptions = array();
		$this->abbr_word_re = '';
		
		parent::teardown();
	}
	
	
	### HTML Block Parser ###
	
	# Tags that are always treated as block tags:
	var $block_tags_re = 'p|div|h[1-6]|blockquote|pre|table|dl|ol|ul|address|form|fieldset|iframe|hr|legend';
	
	# Tags treated as block tags only if the opening tag is alone on it's line:
	var $context_block_tags_re = 'script|noscript|math|ins|del|style';
	
	# Tags where markdown="1" default to span mode:
	var $contain_span_tags_re = 'p|h[1-6]|li|dd|dt|td|th|legend|address';
	
	# Tags which must not have their contents modified, no matter where 
	# they appear:
	var $clean_tags_re = 'script|math';
	
	# Tags that do not need to be closed.
	var $auto_close_tags_re = 'hr|img';
	

	function hashClean($text) {
	#
	# Called whenever a tag must be hashed when a function insert a "clean" tag
	# in $text, it pass through this function and is automaticaly escaped, 
	# blocking invalid nested overlap.
	#
		return $this->hashPart($text, 'C');
	}


	function doHeaders($text) {
	#
	# Redefined to add id attribute support.
	#
		# Setext-style headers:
		#	  Header 1  {#header1}
		#	  ========
		#  
		#	  Header 2  {#header2}
		#	  --------
		#
		$text = preg_replace_callback(
			'{
				(^.+?)								# $1: Header text
				(?:[ ]+\{\#([-_:a-zA-Z0-9]+)\})?	# $2: Id attribute
				[ ]*\n(=+|-+)[ ]*\n+				# $3: Header footer
			}mx',
			array(&$this, '_doHeaders_callback_setext'), $text);

		# atx-style headers:
		#	# Header 1        {#header1}
		#	## Header 2       {#header2}
		#	## Header 2 with closing hashes ##  {#header3}
		#	...
		#	###### Header 6   {#header2}
		#
		$text = preg_replace_callback('{
				^(\#{1,6})	# $1 = string of #\'s
				[ ]*
				(.+?)		# $2 = Header text
				[ ]*
				\#*			# optional closing #\'s (not counted)
				(?:[ ]+\{\#([-_:a-zA-Z0-9]+)\})? # id attribute
				[ ]*
				\n+
			}xm',
			array(&$this, '_doHeaders_callback_atx'), $text);

		return $text;
	}
	function _doHeaders_attr($attr) {
		if (empty($attr))  return "";
		return " id=\"$attr\"";
	}
	function _doHeaders_callback_setext($matches) {
		if ($matches[3] == '-' && preg_match('{^- }', $matches[1]))
			return $matches[0];
		$level = $matches[3]{0} == '=' ? 1 : 2;
		$attr  = $this->_doHeaders_attr($id =& $matches[2]);
		$block = "<h$level$attr>".$this->runSpanGamut($matches[1])."</h$level>";
		return "\n" . $this->hashBlock($block) . "\n\n";
	}
	function _doHeaders_callback_atx($matches) {
		$level = strlen($matches[1]);
		$attr  = $this->_doHeaders_attr($id =& $matches[3]);
		$block = "<h$level$attr>".$this->runSpanGamut($matches[2])."</h$level>";
		return "\n" . $this->hashBlock($block) . "\n\n";
	}


	function doTables($text) {
	#
	# Form HTML tables.
	#
		$less_than_tab = $this->tab_width - 1;
		#
		# Find tables with leading pipe.
		#
		#	| Header 1 | Header 2
		#	| -------- | --------
		#	| Cell 1   | Cell 2
		#	| Cell 3   | Cell 4
		#
		$text = preg_replace_callback('
			{
				(
					(?:[ \t]*[\|])([^\n]*)\n
					(?:[ \t]*[\|][\-\| \t]*)\n
					(
						(?:[ \t]*[\|][^\n]*\n)*
						(?:[ \t]*[\|][^\n]*)+
					)
				)
			}xm',
			array(&$this, '_DoTable_callback1'), $text);
		
		#
		# Find tables without leading pipe.
		#
		#	Header 1 | Header 2
		#	-------- | --------
		#	Cell 1   | Cell 2
		#	Cell 3   | Cell 4
		#
		$text = preg_replace_callback('
			{
				(
					(?:[ \t]*)([^\n]+[\|][^\n]*)\n
					(?:[ \t]*[\-]+[ ]*[\|][\-\| ]*)\n
					(
						(?:[ \t]*[^\n]*[\|][^\n]*\n)*
						(?:[ \t]*[^\n]*[\|][^\n]*)+
					)
				)
			}xm',
			array(&$this, '_DoTable_callback2'), $text);

		return $text;
	}

	function _doTable_callback1($matches) {
		return $this->_doTable($matches, '/(?:\n|^)[ \t]*[\|]([^\n]*)/m');
	}
	function _doTable_callback2($matches) {
		return $this->_doTable($matches, '/(?:\n|^)[ \t]*([^\n]*[\|][^\n]*)/m');
	}

	function _doTable($matches, $lineRegex) {
		$m2 = preg_replace('/[\|]\s*$/', "", $matches[2]);
		$headers = explode('|', $m2);

		$tableBlock = "";

		$tableBlock .= "<table>\n";
		$tableBlock .= "<thead>\n  <tr>\n";

		if (count($headers) !== 1 || $headers[0] !== '') {
			for ($i = 0; $i < count($headers); $i++) {
				$_headers = $this->runSpanGamut(preg_replace('/(^\s+)|(\s+$)/m', '', $headers[$i]));
				$tableBlock .= "    <th>" . $_headers . "</th>\n";
			}
		}

		$tableBlock .= "  </tr>\n</thead>\n";
		$tableBlock .= "<tbody>\n";

		$lines = preg_replace_callback($lineRegex, array(&$this, '_doTable_callback_sub'), $matches[3]);
		$lines = explode("\n", $lines);
		$lines = array_splice($lines, 0, count($lines) - 1);

		for ($i = 0; $i < count($lines); $i++) {
			$tableBlock .= "  <tr>\n";

			$bodies = explode('|', $lines[$i]);
			if (count($headers) < count($bodies)) {
				$tail = implode('|', array_splice($bodies, count($headers) - 1, count($bodies) - 1));
				$bodies = array_splice($bodies, 0, count($headers) - 1);
				$bodies[] = $tail;
			}
			if (count($bodies) !== 1 || $bodies[0] !== '') {
				for ($j = 0; $j < count($bodies); $j++) {
					$_body = $this->runSpanGamut(preg_replace('/(^\s+)|(\s+$)/m', '', $bodies[$j]));
					$tableBlock .= "    <td>" . $_body . "</td>\n";
				}
			}

			$tableBlock .= "  </tr>\n";
		}

		$tableBlock .= "</tbody>\n";
		$tableBlock .= "</table>";

		return $this->hashBlock($tableBlock) . "\n";
	}

	function _doTable_callback_sub($matches) {
		$m1 = preg_replace('/[\|]\s*$/', "", $matches[1]);
		return $m1 . "\n";
	}

	
	function doDefLists($text) {
	#
	# Form HTML definition lists.
	#
		$less_than_tab = $this->tab_width - 1;

		# Re-usable pattern to match any entire dl list:
		$whole_list_re = '(?>
			(								# $1 = whole list
			  (								# $2
				[ ]{0,'.$less_than_tab.'}
				((?>.*\S.*\n)+)				# $3 = defined term
				\n?
				[ ]{0,'.$less_than_tab.'}:[ ]+ # colon starting definition
			  )
			  (?s:.+?)
			  (								# $4
				  \z
				|
				  \n{2,}
				  (?=\S)
				  (?!						# Negative lookahead for another term
					[ ]{0,'.$less_than_tab.'}
					(?: \S.*\n )+?			# defined term
					\n?
					[ ]{0,'.$less_than_tab.'}:[ ]+ # colon starting definition
				  )
				  (?!						# Negative lookahead for another definition
					[ ]{0,'.$less_than_tab.'}:[ ]+ # colon starting definition
				  )
			  )
			)
		)'; // mx

		$text = preg_replace_callback('{
				(?>\A\n?|(?<=\n\n))
				'.$whole_list_re.'
			}mx',
			array(&$this, '_doDefLists_callback'), $text);

		return $text;
	}
	function _doDefLists_callback($matches) {
		# Re-usable patterns to match list item bullets and number markers:
		$list = $matches[1];
		
		# Turn double returns into triple returns, so that we can make a
		# paragraph for the last item in a list, if necessary:
		$result = trim($this->processDefListItems($list));
		$result = "<dl>\n" . $result . "\n</dl>";
		return $this->hashBlock($result) . "\n\n";
	}


	function processDefListItems($list_str) {
	#
	#	Process the contents of a single definition list, splitting it
	#	into individual term and definition list items.
	#
		$less_than_tab = $this->tab_width - 1;
		
		# trim trailing blank lines:
		$list_str = preg_replace("/\n{2,}\\z/", "\n", $list_str);

		# Process definition terms.
		$list_str = preg_replace_callback('{
			(?>\A\n?|\n\n+)					# leading line
			(								# definition terms = $1
				[ ]{0,'.$less_than_tab.'}	# leading whitespace
				(?![:][ ]|[ ])				# negative lookahead for a definition 
											#   mark (colon) or more whitespace.
				(?> \S.* \n)+?				# actual term (not whitespace).	
			)			
			(?=\n?[ ]{0,3}:[ ])				# lookahead for following line feed 
											#   with a definition mark.
			}xm',
			array(&$this, '_processDefListItems_callback_dt'), $list_str);

		# Process actual definitions.
		$list_str = preg_replace_callback('{
			\n(\n+)?						# leading line = $1
			(								# marker space = $2
				[ ]{0,'.$less_than_tab.'}	# whitespace before colon
				[:][ ]+						# definition mark (colon)
			)
			((?s:.+?))						# definition text = $3
			(?= \n+ 						# stop at next definition mark,
				(?:							# next term or end of text
					[ ]{0,'.$less_than_tab.'} [:][ ]	|
					<dt> | \z
				)						
			)					
			}xm',
			array(&$this, '_processDefListItems_callback_dd'), $list_str);

		return $list_str;
	}
	function _processDefListItems_callback_dt($matches) {
		$terms = explode("\n", trim($matches[1]));
		$text = '';
		foreach ($terms as $term) {
			$term = $this->runSpanGamut(trim($term));
			$text .= "\n<dt>" . $term . "</dt>";
		}
		return $text . "\n";
	}
	function _processDefListItems_callback_dd($matches) {
		$leading_line	= $matches[1];
		$marker_space	= $matches[2];
		$def			= $matches[3];

		if ($leading_line || preg_match('/\n{2,}/', $def)) {
			# Replace marker with the appropriate whitespace indentation
			$def = str_repeat(' ', strlen($marker_space)) . $def;
			$def = $this->runBlockGamut($this->outdent($def . "\n\n"));
			$def = "\n". $def ."\n";
		}
		else {
			$def = rtrim($def);
			$def = $this->runSpanGamut($this->outdent($def));
		}

		return "\n<dd>" . $def . "</dd>\n";
	}


	function doFencedCodeBlocks($text) {
	#
	# Adding the fenced code block syntax to regular Markdown:
	#
	# ~~~
	# Code block
	# ~~~
	#
		$less_than_tab = $this->tab_width;
		
		$text = preg_replace_callback('{
				(?:\n|\A)
				# 1: Opening marker
				(
					~{3,} # Marker: three tilde or more.
				)
				[ ]* \n # Whitespace and newline following marker.
				
				# 2: Content
				(
					(?>
						(?!\1 [ ]* \n)	# Not a closing marker.
						.*\n+
					)+
				)
				
				# Closing marker.
				\1 [ ]* \n
			}xm',
			array(&$this, '_doFencedCodeBlocks_callback'), $text);

		return $text;
	}
	function _doFencedCodeBlocks_callback($matches) {
		$codeblock = $matches[2];
		$codeblock = htmlspecialchars($codeblock, ENT_NOQUOTES);
		$codeblock = preg_replace_callback('/^\n+/',
			array(&$this, '_doFencedCodeBlocks_newlines'), $codeblock);
		$codeblock = "<pre><code>$codeblock</code></pre>";
		return "\n\n".$this->hashBlock($codeblock)."\n\n";
	}
	function _doFencedCodeBlocks_newlines($matches) {
		return str_repeat("<br$this->empty_element_suffix", 
			strlen($matches[0]));
	}


	#
	# Redefining emphasis markers so that emphasis by underscore does not
	# work in the middle of a word.
	#
	var $em_relist = array(
		''  => '(?:(?<!\*)\*(?!\*)|(?<![a-zA-Z0-9_])_(?!_))(?=\S|$)(?![\.,:;]\s)',
		'*' => '(?<=\S|^)(?<!\*)\*(?!\*)',
		'_' => '(?<=\S|^)(?<!_)_(?![a-zA-Z0-9_])',
		);
	var $strong_relist = array(
		''   => '(?:(?<!\*)\*\*(?!\*)|(?<![a-zA-Z0-9_])__(?!_))(?=\S|$)(?![\.,:;]\s)',
		'**' => '(?<=\S|^)(?<!\*)\*\*(?!\*)',
		'__' => '(?<=\S|^)(?<!_)__(?![a-zA-Z0-9_])',
		);
	var $em_strong_relist = array(
		''    => '(?:(?<!\*)\*\*\*(?!\*)|(?<![a-zA-Z0-9_])___(?!_))(?=\S|$)(?![\.,:;]\s)',
		'***' => '(?<=\S|^)(?<!\*)\*\*\*(?!\*)',
		'___' => '(?<=\S|^)(?<!_)___(?![a-zA-Z0-9_])',
		);


	function formParagraphs($text) {
	#
	#	Params:
	#		$text - string to process with html <p> tags
	#
		# Strip leading and trailing lines:
		$text = preg_replace('/\A\n+|\n+\z/', '', $text);
		
		$grafs = preg_split('/\n{2,}/', $text, -1, PREG_SPLIT_NO_EMPTY);

		#
		# Wrap <p> tags and unhashify HTML blocks
		#
		foreach ($grafs as $key => $value) {
			$value = trim($this->runSpanGamut($value));
			
			# Check if this should be enclosed in a paragraph.
			# Clean tag hashes & block tag hashes are left alone.
			$is_p = !preg_match('/^B\x1A[0-9]+B|^C\x1A[0-9]+C|^J\x1A[0-9]+J$/', $value);
			
			if ($is_p) {
				$value = "<p>$value</p>";
			}
			$grafs[$key] = $value;
		}
		
		# Join grafs in one text, then unhash HTML tags. 
		$text = implode("\n\n", $grafs);
		
		# Finish by removing any tag hashes still present in $text.
		$text = $this->unhash($text);
		
		return $text;
	}
	
	
	### Footnotes
	
	function stripFootnotes($text) {
	#
	# Strips link definitions from text, stores the URLs and titles in
	# hash references.
	#
		$less_than_tab = $this->tab_width - 1;

		# Link defs are in the form: [^id]: url "optional title"
		$text = preg_replace_callback('{
			^[ ]{0,'.$less_than_tab.'}\[\^(.+?)\][ ]?:	# note_id = $1
			  [ ]*
			  \n?					# maybe *one* newline
			(						# text = $2 (no blank lines allowed)
				(?:					
					.+				# actual text
				|
					\n				# newlines but 
					(?!\[\^.+?\]:\s)# negative lookahead for footnote marker.
					(?!\n+[ ]{0,3}\S)# ensure line is not blank and followed 
									# by non-indented content
				)*
			)		
			}xm',
			array(&$this, '_stripFootnotes_callback'),
			$text);
		return $text;
	}
	function _stripFootnotes_callback($matches) {
		$note_id = $this->fn_id_prefix . $matches[1];
		$this->footnotes[$note_id] = $this->outdent($matches[2]);
		return ''; # String that will replace the block
	}


	function doFootnotes($text) {
	#
	# Replace footnote references in $text [^id] with a special text-token 
	# which will be replaced by the actual footnote marker in appendFootnotes.
	#
		if (!$this->in_anchor) {
			$text = preg_replace('{\[\^(.+?)\]}', "F\x1Afn:\\1\x1A:", $text);
		}
		return $text;
	}

	
	function appendFootnotes($text) {
	#
	# Append footnote list to text.
	#
		$text = preg_replace_callback('{F\x1Afn:(.*?)\x1A:}', 
			array(&$this, '_appendFootnotes_callback'), $text);
	
		if (!empty($this->footnotes_ordered)) {
			$text .= "\n\n";
			$text .= "<div class=\"footnotes\">\n";
			$text .= "<hr". $this->empty_element_suffix ."\n";
			$text .= "<ol>\n\n";
			
			$attr = " rev=\"footnote\"";
			if ($this->fn_backlink_class != "") {
				$class = $this->fn_backlink_class;
				$class = $this->encodeAttribute($class);
				$attr .= " class=\"$class\"";
			}
			if ($this->fn_backlink_title != "") {
				$title = $this->fn_backlink_title;
				$title = $this->encodeAttribute($title);
				$attr .= " title=\"$title\"";
			}
			$num = 0;
			
			while (!empty($this->footnotes_ordered)) {
				$footnote = reset($this->footnotes_ordered);
				$note_id = key($this->footnotes_ordered);
				unset($this->footnotes_ordered[$note_id]);
				
				$footnote .= "\n"; # Need to append newline before parsing.
				$footnote = $this->runBlockGamut("$footnote\n");				
				$footnote = preg_replace_callback('{F\x1Afn:(.*?)\x1A:}', 
					array(&$this, '_appendFootnotes_callback'), $footnote);
				
				$attr = str_replace("%%", ++$num, $attr);
				$note_id = $this->encodeAttribute($note_id);
				
				# Add backlink to last paragraph; create new paragraph if needed.
				$backlink = "<a href=\"#fnref:$note_id\"$attr>&#8617;</a>";
				if (preg_match('{</p>$}', $footnote)) {
					$footnote = substr($footnote, 0, -4) . "&#160;$backlink</p>";
				} else {
					$footnote .= "\n\n<p>$backlink</p>";
				}
				
				$text .= "<li id=\"fn:$note_id\">\n";
				$text .= $footnote . "\n";
				$text .= "</li>\n\n";
			}
			
			$text .= "</ol>\n";
			$text .= "</div>";
		}
		return $text;
	}
	function _appendFootnotes_callback($matches) {
		$node_id = $this->fn_id_prefix . $matches[1];
		
		# Create footnote marker only if it has a corresponding footnote *and*
		# the footnote hasn't been used by another marker.
		if (isset($this->footnotes[$node_id])) {
			# Transfert footnote content to the ordered list.
			$this->footnotes_ordered[$node_id] = $this->footnotes[$node_id];
			unset($this->footnotes[$node_id]);
			
			$num = $this->footnote_counter++;
			$attr = " rel=\"footnote\"";
			if ($this->fn_link_class != "") {
				$class = $this->fn_link_class;
				$class = $this->encodeAttribute($class);
				$attr .= " class=\"$class\"";
			}
			if ($this->fn_link_title != "") {
				$title = $this->fn_link_title;
				$title = $this->encodeAttribute($title);
				$attr .= " title=\"$title\"";
			}
			
			$attr = str_replace("%%", $num, $attr);
			$node_id = $this->encodeAttribute($node_id);
			
			return
				"<sup id=\"fnref:$node_id\">".
				"<a href=\"#fn:$node_id\"$attr>$num</a>".
				"</sup>";
		}
		
		return "[^".$matches[1]."]";
	}
		
	
	### Abbreviations ###
	
	function stripAbbreviations($text) {
	#
	# Strips abbreviations from text, stores titles in hash references.
	#
		$less_than_tab = $this->tab_width - 1;

		# Link defs are in the form: [id]*: url "optional title"
		$text = preg_replace_callback('{
			^[ ]{0,'.$less_than_tab.'}\*\[(.+?)\][ ]?:	# abbr_id = $1
			(.*)					# text = $2 (no blank lines allowed)	
			}xm',
			array(&$this, '_stripAbbreviations_callback'),
			$text);
		return $text;
	}
	function _stripAbbreviations_callback($matches) {
		$abbr_word = $matches[1];
		$abbr_desc = $matches[2];
		if ($this->abbr_word_re)
			$this->abbr_word_re .= '|';
		$this->abbr_word_re .= preg_quote($abbr_word);
		$this->abbr_desciptions[$abbr_word] = trim($abbr_desc);
		return ''; # String that will replace the block
	}
	
	
	function doAbbreviations($text) {
	#
	# Find defined abbreviations in text and wrap them in <abbr> elements.
	#
		if ($this->abbr_word_re) {
			// cannot use the /x modifier because abbr_word_re may 
			// contain significant spaces:
			$text = preg_replace_callback('{'.
				'(?<![\w\x1A])'.
				'(?:'.$this->abbr_word_re.')'.
				'(?![\w\x1A])'.
				'}', 
				array(&$this, '_doAbbreviations_callback'), $text);
		}
		return $text;
	}
	function _doAbbreviations_callback($matches) {
		$abbr = $matches[0];
		if (isset($this->abbr_desciptions[$abbr])) {
			$desc = $this->abbr_desciptions[$abbr];
			if (empty($desc)) {
				return $this->hashPart("<abbr>$abbr</abbr>");
			} else {
				$desc = $this->encodeAttribute($desc);
				return $this->hashPart("<abbr title=\"$desc\">$abbr</abbr>");
			}
		} else {
			return $matches[0];
		}
	}

}


/*

PHP Markdown Extra
==================

Description
-----------

This is a PHP port of the original Markdown formatter written in Perl 
by John Gruber. This special "Extra" version of PHP Markdown features 
further enhancements to the syntax for making additional constructs 
such as tables and definition list.

Markdown is a text-to-HTML filter; it translates an easy-to-read /
easy-to-write structured text format into HTML. Markdown's text format
is most similar to that of plain text email, and supports features such
as headers, *emphasis*, code blocks, blockquotes, and links.

Markdown's syntax is designed not as a generic markup language, but
specifically to serve as a front-end to (X)HTML. You can use span-level
HTML tags anywhere in a Markdown document, and you can use block level
HTML tags (like <div> and <table> as well).

For more information about Markdown's syntax, see:

<http://daringfireball.net/projects/markdown/>


Bugs
----

To file bug reports please send email to:

<michel.fortin@michelf.com>

Please include with your report: (1) the example input; (2) the output you
expected; (3) the output Markdown actually produced.


Version History
--------------- 

See the readme file for detailed release notes for this version.


Copyright and License
---------------------

PHP Markdown & Extra  
Copyright (c) 2004-2009 Michel Fortin  
<http://michelf.com/>  
All rights reserved.

Based on Markdown  
Copyright (c) 2003-2006 John Gruber   
<http://daringfireball.net/>   
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are
met:

*	Redistributions of source code must retain the above copyright notice,
	this list of conditions and the following disclaimer.

*	Redistributions in binary form must reproduce the above copyright
	notice, this list of conditions and the following disclaimer in the
	documentation and/or other materials provided with the distribution.

*	Neither the name "Markdown" nor the names of its contributors may
	be used to endorse or promote products derived from this software
	without specific prior written permission.

This software is provided by the copyright holders and contributors "as
is" and any express or implied warranties, including, but not limited
to, the implied warranties of merchantability and fitness for a
particular purpose are disclaimed. In no event shall the copyright owner
or contributors be liable for any direct, indirect, incidental, special,
exemplary, or consequential damages (including, but not limited to,
procurement of substitute goods or services; loss of use, data, or
profits; or business interruption) however caused and on any theory of
liability, whether in contract, strict liability, or tort (including
negligence or otherwise) arising in any way out of the use of this
software, even if advised of the possibility of such damage.

*/
?>
