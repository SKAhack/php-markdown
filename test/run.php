<?php

set_include_path(dirname(__FILE__) . '/../' . PATH_SEPARATOR . get_include_path());

require_once 'markdown.php';

class MarkdownTest extends PHPUnit_Framework_TestCase
{
  public function setUp() {
    $this->casePath = "test/cases";
  }

  public function testAnchorsByReference() {
    $name = "anchors-by-reference";
    $this->helper($name);
  }
  public function testAutomaticAnchors() {
    $name = "automatic-anchors";
    $this->helper($name);
  }
  public function testBlockquoteNestedMarkdown() {
    $name = "blockquote-nested-markdown";
    $this->helper($name);
  }
  public function testBlockquote() {
    $name = "blockquote";
    $this->helper($name);
  }
  public function testCodeBlockHtmlEscape() {
    $name = "code-block-html-escape";
    $this->helper($name);
  }
  public function testCodeBlock() {
    $name = "code-block";
    $this->helper($name);
  }
  public function testDoublineList() {
    $name = "doubline-list";
    $this->helper($name);
  }
  public function testEmphasis() {
    $name = "emphasis";
    $this->helper($name);
  }
  public function testEscapedNumberPeriod() {
    $name = "escaped-number-period";
    $this->helper($name);
  }
  public function testEscaping() {
    $name = "escaping";
    $this->helper($name);
  }
//  public function testGithubStyleAtStart() {
//    $name = "github-style-at-start";
//    $this->helper($name);
//  }
//  public function testGithubStyleCodeblock() {
//    $name = "github-style-codeblock";
//    $this->helper($name);
//  }
  public function testH1WithDoubleHash() {
    $name = "h1-with-double-hash";
    $this->helper($name);
  }
  public function testH1WithEquals() {
    $name = "h1-with-equals";
    $this->helper($name);
  }
  public function testH1WithSingleHash() {
    $name = "h1-with-single-hash";
    $this->helper($name);
  }
  public function testH2WithDashes() {
    $name = "h2-with-dashes";
    $this->helper($name);
  }
  public function testH2WithDoubleHash() {
    $name = "h2-with-double-hash";
    $this->helper($name);
  }
  public function testH2WithSingleHash() {
    $name = "h2-with-single-hash";
    $this->helper($name);
  }
  public function testH3WithDoubleHash() {
    $name = "h3-with-double-hash";
    $this->helper($name);
  }
  public function testH3WithSingleHash() {
    $name = "h3-with-single-hash";
    $this->helper($name);
  }
  public function testH4WithSingleHash() {
    $name = "h4-with-single-hash";
    $this->helper($name);
  }
  public function testH5WithSingleHash() {
    $name = "h5-with-single-hash";
    $this->helper($name);
  }
  public function testH6WithSingleHash() {
    $name = "h6-with-single-hash";
    $this->helper($name);
  }
  public function testHorizontalRules() {
    $name = "horizontal-rules";
    $this->helper($name);
  }
  public function testHtml5StruturalTags() {
    $name = "html5-strutural-tags";
    $this->helper($name);
  }
  public function testImages() {
    $name = "images";
    $this->helper($name);
  }
  public function testImplicitAnchors() {
    $name = "implicit-anchors";
    $this->helper($name);
  }
  public function testInlineAnchors() {
    $name = "inline-anchors";
    $this->helper($name);
  }
  public function testInlineCode() {
    $name = "inline-code";
    $this->helper($name);
  }
  public function testInlineEscapedChars() {
    $name = "inline-escaped-chars";
    $this->helper($name);
  }
  public function testInlineStyleTag() {
    $name = "inline-style-tag";
    $this->helper($name);
  }
  public function testLazyBlockquote() {
    $name = "lazy-blockquote";
    $this->helper($name);
  }
  public function testListWithBlockquote() {
    $name = "list-with-blockquote";
    $this->helper($name);
  }
  public function testListWithCode() {
    $name = "list-with-code";
    $this->helper($name);
  }
  public function testMultiParagraphList() {
    $name = "multi-paragraph-list";
    $this->helper($name);
  }
  public function testMultilineUnorderedList() {
    $name = "multiline-unordered-list";
    $this->helper($name);
  }
  public function testNestedBlockquote() {
    $name = "nested-blockquote";
    $this->helper($name);
  }
  public function testOrderedListSameNumber() {
    $name = "ordered-list-same-number";
    $this->helper($name);
  }
  public function testOrderedListWrongNumbers() {
    $name = "ordered-list-wrong-numbers";
    $this->helper($name);
  }
  public function testOrderedList() {
    $name = "ordered-list";
    $this->helper($name);
  }
  public function testRelativeAnchors() {
    $name = "relative-anchors";
    $this->helper($name);
  }
  public function testSimpleParagraph() {
    $name = "simple-paragraph";
    $this->helper($name);
  }
  public function testStrong() {
    $name = "strong";
    $this->helper($name);
  }
  public function testTable() {
    $name = "table";
    $this->helper($name);
  }
  public function testTable2() {
    $name = "table2";
    $this->helper($name);
  }
  public function testTagEscape() {
    $name = "tag-escape";
    $this->helper($name);
  }
  public function testUnorderedListAsterisk() {
    $name = "unordered-list-asterisk";
    $this->helper($name);
  }
  public function testUnorderedListMinus() {
    $name = "unordered-list-minus";
    $this->helper($name);
  }
  public function testUnorderedListPlus() {
    $name = "unordered-list-plus";
    $this->helper($name);
  }
  public function testUrlAutolinking() {
    $name = "url-autolinking";
    $this->helper($name);
  }

  // protected

  protected function helper($name, $debug = false) {
    $html = file_get_contents($this->casePath . "/" . $name . ".html");
    $md   = file_get_contents($this->casePath . "/" . $name . ".md");

    $html = trim($html);
    $md = trim(Markdown($md));

    $tmp = array();
    foreach (explode("\n", $html) as $i) {
      $tmp[] = trim($i);
    }
    $html = implode("\n", $tmp);

    $tmp = array();
    foreach (explode("\n", $md) as $i) {
      $tmp[] = trim($i);
    }
    $md = implode("\n", $tmp);

    $html = preg_replace('/ /m', "·", $html);
    $html = preg_replace('/\n/m', "•\n", $html);
    $md = preg_replace('/ /m', "·", $md);
    $md = preg_replace('/\n/m', "•\n", $md);

    if ($debug) {
      echo "html 0000000000\n";
      echo $html;
      echo "md 000000000000\n";
      echo $md;
      echo "00000000000000\n";
    }

    $this->assertEquals($html, $md);
  }
}
