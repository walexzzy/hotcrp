<?php
// src/help/h_tags.php -- HotCRP help functions
// HotCRP is Copyright (c) 2006-2017 Eddie Kohler and Regents of the UC
// Distributed under an MIT-like license; see LICENSE

class HelpTopic_Tags {
    private $conf;
    private $user;
    private $hth;

    function __construct(Contact $user, $hth) {
        $this->conf = $user->conf;
        $this->user = $user;
        $this->hth = $hth;
    }

    function _current_tag_list($property) {
        $ct = [];
        if ($this->user->isPC)
            $ct = $this->conf->tags()->filter($property);
        return empty($ct) ? "" : " (currently "
            . join(", ", array_map(function ($t) {
                return "“" . $this->hth->search_link("#{$t->tag}", $t->tag) . "”";
            }, $ct)) . ")";
    }

    function _vote_tag() {
        $vt = [];
        if ($this->user->isPC)
            $vt = $this->conf->tags()->filter("vote");
        return empty($vt) ? "vote" : current($vt)->tag;
    }

    private function _change_this_setting() {
        if ($this->user->privChair)
            return " (" . $this->hth->link("Change this setting", hoturl("settings", "group=tags")) . ") ";
        else
            return "";
    }

    function render_intro() {
        $conflictmsg = "";
        if ($this->user->isPC && !$this->conf->tag_seeall)
            $conflictmsg = " and conflicted PC members";

        echo "<p>PC members and administrators can attach tag names to papers.
It’s easy to change tags and to list all papers with a given tag,
and <em>ordered</em> tags preserve a particular paper order.
Tags also affect color highlighting in paper lists.</p>

<p>Tags are visible to the PC and hidden from authors$conflictmsg.
<em>Twiddle tags</em>, with names like “#~tag”, are visible only
to their creators.  Tags with two twiddles, such as “#~~tag”, are
visible only to PC chairs.</p>";
    }

    function render_finding() {
        $hth = $this->hth;
        echo $hth->subhead("Finding tags");
        echo "<p>A paper’s tags are shown like this on the paper page:</p>

<div class='pspcard_container' style='position:static'><div class='pspcard'><div class='pspcard_body'>
<div class='psc psc1'>
 <div class='pst'>
  <span class='psfn'>Tags</span>
  <span class='pstedit'><a class='xx'><span style='display:inline-block;position:relative;width:16px'>",
    Ht::img("edit48.png", "[Edit]", "editimg"), "</span>&nbsp;<u class='x'>Edit</u></a></span>
  <hr class='c' /></div>
<div class='psv'><div class='taghl'>#earlyaccept</div></div></div>
</div></div></div><hr class='c' />

<p>To find all papers with tag “#discuss”:&nbsp; ", $hth->search_form("#discuss"), "</p>

<p>You can also search with “", $hth->search_link("show:tags"), "” to see each
paper’s tags, or “", $hth->search_link("show:#tagname"), "” to see a particular tag
as a column.</p>

<p>Tags are only shown to PC members and administrators. ";
        if ($this->user->isPC) {
            if ($this->conf->tag_seeall)
                echo "Currently PC members can see tags for any paper, including conflicts.";
            else
                echo "They are hidden from conflicted PC members; for instance, if a PC member searches for a tag, the result will never include their conflicts.";
            echo $this->_change_this_setting();
        }
        echo "Additionally, twiddle tags, which have names like “#~tag”, are
visible only to their creators; each PC member has an independent set.
Tags are not case sensitive.</p>";
    }

    function render_changing() {
        $hth = $this->hth;
        echo $hth->subhead("Changing tags", "changing");
        echo "
<ul>
<li><p><strong>For one paper:</strong> Go to a paper page, select the Tags box’s
“Edit” link, and enter tags separated by spaces.</p>

<p>" . Ht::img("extagsset.png", "[Tag entry on review screen]", ["width" => 142, "height" => 87]) . "</p></li>

<li><p><strong>For many papers:</strong> <a href=\"" . hoturl("search")
. "\">Search</a> for papers, select them, and use the action area underneath the
search list. <b>Add</b> adds tags to the selected papers, <b>Remove</b> removes
tags from the selected papers, and <b>Define</b> adds the tag to the selected
papers and removes it from all others.  The chair-only <b>Clear twiddle</b>
action removes a tag and all users’ matching twiddle tags.</p>

<p>" . Ht::img("extagssearch.png", "[Setting tags on the search page]", ["width" => 510, "height" => 94]) . "</p></li>

<li><p><strong>With search keywords:</strong> Search for “"
. $hth->search_link("edit:tag:tagname") . "” to add tags with checkboxes;
search for “" . $hth->search_link("edit:tagval:tagname") . "” to type in <a
href='#values'>tag values</a>; or search for “" . $hth->search_link("edit:tags") . "”
to edit papers’ full tag lists.</p>

<p>" . Ht::img("extagseditkw.png", "[Tag editing search keywords]", ["width" => 543, "height" => 133]) . "</p></li>

<li><p><strong>In bulk:</strong> Administrators can also upload tag
assignments using <a href='" .
hoturl("bulkassign") . "'>bulk assignment</a>.</p></li>

</ul>

<p>Although any PC member can view or search
most tags, certain tags may be changed only by administrators",
    $this->_current_tag_list("chair"), ".", $this->_change_this_setting(), "</p>";
    }

    function render_values() {
        $hth = $this->hth;
        echo $hth->subhead("Tag values and discussion orders", "values");
        echo "<p>Tags have optional numeric values, which are displayed as
“#tag#100”. Search for “" . $hth->search_link("order:tag") . "” to sort tagged
papers by value. You can also search for specific values with search terms
like “" . $hth->search_link("#discuss#2") . "” or “" . $hth->search_link("#discuss>1") .
"”.</p>

<p>It’s common to assign increasing tag values to a set of papers.  Do this
using the <a href='" . hoturl("search") . "'>search screen</a>.  Search for the
papers you want, sort them into the right order, select their checkboxes, and
choose <b>Define order</b> in the tag action area.  If no sort gives what
you want, search for the desired paper numbers in order—for instance,
“" . $hth->search_link("4 1 12 9") . "”—then <b>Select all</b> and <b>Define
order</b>. To add new papers at the end of an existing discussion order, use
<b>Add to order</b>. To insert papers into an existing order, use <b>Add to
order</b> with a tag value; for example, to insert starting at value 5, use
<b>Add to order</b> with “#tag#5”.  The rest of the order is renumbered to
accommodate the insertion.</p>

<p>Even easier, you can <em>drag</em> papers into order using a search like “"
. $hth->search_link("editsort:#tag") . "”.</p>

<p><b>Define order</b> might assign values “#tag#1”,
“#tag#3”, “#tag#6”, and “#tag#7”
to adjacent papers.  The gaps make it harder to infer
conflicted papers’ positions.  (Any given gap might or might not hold a
conflicted paper.)  The <b>Define gapless order</b> action assigns
strictly sequential values, like “#tag#1”,
“#tag#2”, “#tag#3”, “#tag#4”.
<b>Define order</b> is better for most purposes.</p>

<p>The <a href=\"" . hoturl("autoassign", "a=discorder") . "\">autoassigner</a>
has special support for creating discussion orders. It tries to group papers
with similar PC conflicts, which can make the meeting run smoother.</p>";
    }

    function render_colors() {
        $hth = $this->hth;
        echo $hth->subhead("Tag colors, badges, and emoji", "colors");
        echo "<p>Tags “red”, “orange”, “yellow”, “green”, “blue”, “purple”, “gray”, and
“white” act as highlight colors. For example, papers tagged with “#red” will
appear <span class=\"tagcolorspan redtag\">red</span> in paper lists (for people
who can see that tag).  Tag a paper “#~red” to make it red only on your display.
Other styles are available; try “#bold”, “#italic”, “#big”, “#small”, and
“#dim”. The <a href='" .
hoturl("settings", "group=tags") . "'>settings page</a> can associate other tags
with colors so that, for example, “" . $hth->search_link("#reject") . "” papers appear
gray.</p>

<p>The " . Ht::link("settings page", hoturl("settings", "group=tags")) . " can
declare certain tags as <span class=\"badge normalbadge\">badges</span>, which
display near titles.</p>

<p><a href=\"http://www.emoji-cheat-sheet.com/\">Emoji codes</a> like “:fire:”
and “:confused:” can be used as tags. The corresponding emoji displays next to
the paper title. <a href=\"#values\">Tag values</a> show multiple emoji,
so “#:star:#5” shows five stars.</p>

<p>" . Ht::img("extagcolors.png", "[Tag colors, badges, and emoji]", ["width" => 498, "height" => 151]) . "</p></li>";
    }

    function render_examples() {
        echo $this->hth->subhead("Examples");
        echo "<p>Here are some example ways to use tags.</p>\n";
        $this->hth->echo_topic("tagexamples");
    }

    function render_example_r1reject() {
        echo "<p><strong>Skip low-ranked submissions.</strong> Mark
low-ranked submissions with tag “#r1reject”, then ask the PC to " .
$this->hth->search_link("#r1reject", "search for “#r1reject”") . ". PC members can check the list
for papers they’d like to discuss anyway. They can email the chairs about
such papers, or remove the tag themselves. (You might make the
“#r1reject” tag chair-only so an evil PC member couldn’t add it to a
high-ranked paper, but it’s usually better to trust the PC.)</p>\n";
    }

    function render_example_controversial() {
        echo "<p><strong>Mark controversial papers that would benefit from additional review.</strong>
 PC members could add the “#controversial” tag when the current reviewers disagree.
 A ", $this->hth->link("search", hoturl("search", ["q" => "#controversial"])),
    " shows where the PC thinks more review is needed.</p>\n";
    }

    function render_example_pcpaper() {
        echo "<p><strong>Mark PC-authored papers for extra scrutiny.</strong>
 First, <a href='" . hoturl("search", "t=s&amp;qt=au") . "'>search for PC members’ last names in author fields</a>.
 Check for accidental matches and select the papers with PC members as authors, then use the action area below the search list to add the tag “#pcpaper”.
 A <a href='" . hoturl("search", "t=s&amp;q=-%23pcpaper") . "'>search</a> shows papers without PC authors.</p>\n";
    }

    function render_example_allotment() {
        $vt = $this->_vote_tag();
        echo "<p><strong>Vote for papers.</strong>
 The chair can define tags used for allotment voting", $this->_current_tag_list("vote"), ".",
    $this->_change_this_setting(),
    " Each PC member is assigned an allotment of votes to distribute among papers.
 For instance, if “#{$vt}” were a voting tag with an allotment of 10, then a PC member could assign 5 votes to a paper by adding the twiddle tag “#~{$vt}#5”.
 The system automatically sums PC members’ votes into the public “#{$vt}” tag.
 To search for papers by vote count, search for “", $this->hth->search_link("rorder:$vt"),
    "”. (", $this->hth->help_link("votetags"), ")</p>\n";
    }

    function render_example_rank() {
        echo "<p><strong>Rank papers.</strong>
 Each PC member can set tags indicating their preference ranking for papers.
 For instance, a PC member’s favorite paper would get tag “#~rank#1”, the next favorite “#~rank#2”, and so forth.
 The chair can then combine these rankings into a global preference order using a Condorcet method.
 (", $this->hth->help_link("ranking"), ")</p>\n";
    }

    function render_example_discuss() {
        echo "<p><strong>Define a discussion order.</strong>
Publishing the order lets PC members prepare to discuss upcoming papers.
Define an ordered tag such as “#discuss”, then ask the PC to ", $this->hth->search_link("order:discuss", "search for “order:discuss”"), ".
The PC can now see the order and use quick links to go from paper to paper.";
        if ($this->user->isPC && !$this->conf->tag_seeall)
            echo " However, since PC members can’t see tags for conflicted papers, each PC member might see a different list.", $this->_change_this_setting();
        echo "</p>\n";
    }

    function render_example_decisions() {
        echo "<p><strong>Mark tentative decisions during the PC meeting</strong> using
“#accept” and “#reject” tags, or mark more granular decisions with tags like “#revisit”
or “#exciting” or “#boring”. After the meeting, use ", $this->hth->search_link("#accept", "Search"),
" &gt; Decide to mark the final decisions. (Or just use the per-paper decision selectors.)</p>\n";
    }
}
