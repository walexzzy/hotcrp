<?php
// help.php -- HotCRP help page
// HotCRP is Copyright (c) 2006-2017 Eddie Kohler and Regents of the UC
// Distributed under an MIT-like license; see LICENSE

require_once("src/initweb.php");

$help_topics = new GroupedExtensions($Me, [
    '{"name":"topics","title":"Help topics","position":-1000000,"priority":1000000,"function":"show_help_topics"}',
    "etc/helptopics.json"
], $Conf->opt("helpTopics"));

$Qreq = make_qreq();
if (!$Qreq->t && preg_match(',\A/(\w+)\z,i', Navigation::path()))
    $Qreq->t = substr(Navigation::path(), 1);
$topic = $Qreq->t ? : "topics";
$want_topic = $help_topics->canonical_group($topic);
if (!$want_topic)
    $want_topic = "topics";
if ($want_topic !== $topic)
    redirectSelf(["t" => $want_topic]);
$topicj = $help_topics->get($topic);

$Conf->header_head($topic === "topics" ? "Help" : "Help - {$topicj->title}");
$Conf->header_body("Help", "help", actionBar());

class HtHead extends Ht {
    public $conf;
    public $user;
    private $_tabletype;
    private $_rowidx;
    private $_help_topics;
    private $_renderers = [];
    function __construct(Contact $user, $help_topics) {
        $this->conf = $user->conf;
        $this->user = $user;
        $this->_help_topics = $help_topics;
    }
    static function subhead($title, $id = null) {
        if ($id || $title)
            return '<h3 class="helppage"' . ($id ? " id=\"{$id}\"" : "") . '>' . $title . "</h3>\n";
        else
            return "";
    }
    function table($tabletype = false) {
        $this->_rowidx = 0;
        $this->_tabletype = $tabletype;
        return $this->_tabletype ? "" : '<table class="helppage"><tbody>';
    }
    function tgroup($title, $id = null) {
        $this->_rowidx = 0;
        if ($this->_tabletype)
            return $this->subhead($title, $id);
        else
            return '<tr><td class="sentry nw" colspan="2"><h4 class="helppage"'
                . ($id ? " id=\"{$id}\"" : "") . '>'
                . $title . "</h4></td></tr>\n";
    }
    function trow($caption, $entry = null) {
        if ($this->_tabletype) {
            $t = "<div class=\"helplist-item k{$this->_rowidx}\">"
                . "<table class=\"helppage\"><tbody><tr><td class=\"helplist-dt\">"
                . $caption
                . "</td><td class=\"helplist-dd\">"
                . $entry . "</td></tr></tbody></table></div>\n";
        } else {
            $t = "<tr class=\"k{$this->_rowidx}\"><td class=\"sentry\"";
            if ((string) $entry === "")
                $t .= ' colspan="2">' . $caption;
            else
                $t .= '>' . $caption . '</td><td class="sentry">' . $entry;
            $t .= "</td></tr>\n";
        }
        $this->_rowidx = 1 - $this->_rowidx;
        return $t;
    }
    function end_table() {
        return $this->_tabletype ? "" : "</tbody></table>\n";
    }
    function search_link($q, $html = null) {
        if (is_string($q))
            $q = ["q" => $q];
        return '<a href="' . hoturl("search", $q) . '">'
            . ($html ? : htmlspecialchars($q["q"])) . '</a>';
    }
    function help_link($topic, $html = null) {
        $group = $this->_help_topics->canonical_group($topic);
        return '<a href="' . hoturl("help", ["t" => $group ? : $topic]) . '">'
            . ($html ? : "Learn more") . '</a>';
    }
    function search_form($q, $size = 20) {
        if (is_string($q))
            $q = ["q" => $q];
        $t = Ht::form_div(hoturl("search"), ["method" => "get", "divclass" => "nw"])
            . Ht::entry("q", $q["q"], ["size" => $size])
            . " &nbsp;"
            . Ht::submit("go", "Search");
        foreach ($q as $k => $v) {
            if ($k !== "q")
                $t .= Ht::hidden($k, $v);
        }
        return $t . "</div></form>";
    }
    function search_trow($q, $entry) {
        return $this->trow($this->search_form($q, 36), $entry);
    }
    function echo_topic($topic) {
        foreach ($this->_help_topics->members($topic) as $gj) {
            Conf::xt_resolve_require($gj);
            if (isset($gj->function))
                call_user_func($gj->function, $this->user, $this);
            else if (isset($gj->renderer))
                call_user_func($gj->renderer, $this->user, $this);
            else if (isset($gj->factory_class) && isset($gj->method)) {
                $klass = $gj->factory_class;
                if (!isset($renderers[$klass]))
                    $renderers[$klass] = new $klass($this->user, $this);
                call_user_func([$renderers[$klass], $gj->method]);
            }
        }
    }
}

$hth = new HtHead($Me, $help_topics);


function show_help_topics() {
    global $help_topics;
    echo "<dl>\n";
    foreach ($help_topics->groups() as $ht) {
        if ($ht->name !== "topics" && isset($ht->title)) {
            echo '<dt><strong><a href="', hoturl("help", "t=$ht->name"), '">', $ht->title, '</a></strong></dt>';
            if (isset($ht->description))
                echo '<dd>', get($ht, "description", ""), '</dd>';
            echo "\n";
        }
    }
    echo "</dl>\n";
}


function search(Contact $user, $hth) {
    echo "<p>All HotCRP paper lists are obtained through flexible
search. Some hints for PC members and chairs:</p>

<ul class='compact'>
<li><div style='display:inline-block'>", $hth->search_form(""), "</div>&nbsp; finds all papers.  (Leave the search field blank.)</li>
<li><div style='display:inline-block'>", $hth->search_form("12"), "</div>&nbsp; finds paper #12.  When entered from a
 <a href='#quicklinks'>quicksearch</a> box, this search will jump to
 paper #12 directly.</li>
<li><a href='" . hoturl("help", "t=keywords") . "'>Search keywords</a>
 let you search specific fields, review scores, and more.</li>
<li>Use <a href='#quicklinks'>quicklinks</a> on paper pages to navigate
 through search results. Typing <code>j</code> and <code>k</code> also goes
 from paper to paper.</li>
<li>On search results pages, shift-click checkboxes to
 select paper ranges.</li>
</ul>";

    echo $hth->subhead("How to search");
    echo "
<p>The default search box returns papers that match
<em>all</em> of the space-separated terms you enter.
To search for words that <em>start</em> with
a prefix, try “term*”.
To search for papers that match <em>some</em> of the terms,
type “term1 OR term2”.
To search for papers that <em>don’t</em> match a term,
try “-term”.  Or select
<a href='" . hoturl("search", "opt=1") . "'>Advanced search</a>
and use “With <b>any</b> of the words” and “<b>Without</b> the words.”</p>

<p>You can search in several paper classes, depending on your role in the
conference. Options include:</p>
<ul class='compact'>
<li><b>Submitted papers</b> &mdash; all submitted papers.</li>
<li><b>All papers</b> &mdash; all papers, including withdrawn and other non-submitted papers.</li>
<li><b>Your submissions</b> &mdash; papers for which you’re a contact.</li>
<li><b>Your reviews</b> &mdash; papers you’ve been assigned to review.</li>
<li><b>Your incomplete reviews</b> &mdash; papers you’ve been assigned to review, but haven’t submitted a review yet.</li>
</ul>

<p>Search won’t show you information you aren’t supposed to see.  For example,
authors can only search their own submissions, and if the conference used
anonymous submission, then only the PC chairs can search by author.</p>

<p>By default, search examines paper titles, abstracts, and authors.
<a href='" . hoturl("search", "opt=1") . "'>Advanced search</a>
can search other fields, including authors/collaborators and reviewers.
Also, <b>keywords</b> search specific characteristics such as titles,
authors, reviewer names, and numbers of reviewers.  For example,
“ti:foo” means “search for ‘foo’ in paper
titles.”  Keywords are listed in the
<a href='" . hoturl("help", "t=keywords") . "'>search keywords reference</a>.</p>";

    echo $hth->subhead("Search results");
    echo "
<p>Click on a paper number or title to jump to that paper.
Search matches are <span class='match'>highlighted</span> on paper screens.
Once on a paper screen use <a href='#quicklinks'>quicklinks</a>
to navigate through the rest of the search matches.</p>

<p>Underneath the paper list is the action area:</p>

" . Ht::img("exsearchaction.png", "[Search action area]") . "<br />

<p>Use the checkboxes to select some papers, then choose an action.
You can:</p>

<ul class='compact'>
<li>Download a <code>.zip</code> file with the selected papers.</li>
<li>Download all reviews for the selected papers.</li>
<li>Download tab-separated text files with authors, PC
 conflicts, review scores, and so forth (some options chairs only).</li>
<li>Add, remove, and define <a href='" . hoturl("help", "t=tags") . "'>tags</a>.</li>
<li>Assign reviewers and mark conflicts (chairs only).</li>
<li>Set decisions (chairs only).</li>
<li>Send mail to paper authors or reviewers (chairs only).</li>
</ul>

<p>Select papers one by one, select in groups by shift-clicking
the checkboxes, or use the “select all” link.
The easiest way to tag a set of papers is
to enter their numbers in the search box, search, “select all,” and add the
tag.</p>";

    echo $hth->subhead("Quicksearch and quicklinks", "quicklinks");
    echo "
<p>Most screens have a quicksearch box in the upper right corner:<br />
" . Ht::img("quicksearchex.png", "[Quicksearch box]") . "<br />
This box supports the full search syntax.  Enter
a paper number, or search terms that match exactly
one paper, to go directly to that paper.</p>

<p>Paper screens have quicklinks that step through search results:<br />
" . Ht::img("pageresultsex.png", "[Quicklinks]") . "<br />
Click on the search description (here, “Submitted papers search”) to return
to the search results.  On many pages, you can press “<code>j</code>” or
“<code>k</code>” to go to the previous or next paper in the list.</p>";
}

function meaningful_pc_tag(Contact $user) {
    if ($user->isPC)
        foreach ($user->conf->pc_tags() as $tag)
            if ($tag !== "pc")
                return $tag;
    return false;
}

function meaningful_round_name(Contact $user) {
    $rounds = $user->conf->round_list();
    for ($i = 1; $i < count($rounds); ++$i)
        if ($rounds[$i] !== ";")
            return $rounds[$i];
    return false;
}

function searchQuickref(Contact $user, $hth) {
    // how to report author searches?
    if ($user->conf->subBlindNever())
        $aunote = "";
    else if (!$user->conf->subBlindAlways())
        $aunote = "<br /><span class='hint'>Search uses fields visible to the searcher. For example, PC member searches do not examine anonymous authors.</span>";
    else
        $aunote = "<br /><span class='hint'>Search uses fields visible to the searcher. For example, PC member searches do not examine authors.</span>";

    // does a reviewer tag exist?
    $retag = meaningful_pc_tag($user) ? : "";

    echo $hth->table(true);
    echo $hth->tgroup("Basics");
    echo $hth->search_trow("", "all papers in the search category");
    echo $hth->search_trow("story", "“story” in title, abstract, authors$aunote");
    echo $hth->search_trow("119", "paper #119");
    echo $hth->search_trow("1 2 5 12-24 kernel", "papers in the numbered set with “kernel” in title, abstract, authors");
    echo $hth->search_trow("\"802\"", "“802” in title, abstract, authors (not paper #802)");
    echo $hth->search_trow("very new", "“very” <em>and</em> “new” in title, abstract, authors");
    echo $hth->search_trow("very AND new", "the same");
    echo $hth->search_trow("\"very new\"", "the phrase “very new” in title, abstract, authors");
    echo $hth->search_trow("very OR new", "<em>either</em> “very” <em>or</em> “new” in title, abstract, authors");
    echo $hth->search_trow("(very AND new) OR newest", "use parentheses to group");
    echo $hth->search_trow("very -new", "“very” <em>but not</em> “new” in title, abstract, authors");
    echo $hth->search_trow("very NOT new", "the same");
    echo $hth->search_trow("ve*", "words that <em>start with</em> “ve” in title, abstract, authors");
    echo $hth->search_trow("*me*", "words that <em>contain</em> “me” in title, abstract, authors");
    echo $hth->tgroup("Title");
    echo $hth->search_trow("ti:flexible", "title contains “flexible”");
    echo $hth->tgroup("Abstract");
    echo $hth->search_trow("ab:\"very novel\"", "abstract contains “very novel”");
    echo $hth->tgroup("Authors");
    echo $hth->search_trow("au:poletto", "author list contains “poletto”");
    if ($user->isPC)
        echo $hth->search_trow("au:pc", "one or more authors are PC members (author email matches PC email)");
    echo $hth->search_trow("au:>4", "more than four authors");
    echo $hth->tgroup("Collaborators");
    echo $hth->search_trow("co:liskov", "collaborators contains “liskov”");
    echo $hth->tgroup("Topics");
    echo $hth->search_trow("topic:link", "selected topics match “link”");

    $oex = array();
    foreach ($user->conf->paper_opts->option_list() as $o)
        $oex = array_merge($oex, $o->example_searches());
    if (!empty($oex)) {
        echo $hth->tgroup("Options");
        foreach ($oex as $extype => $oex) {
            if ($extype === "has") {
                $desc = "paper has “" . htmlspecialchars($oex[1]->name) . "” submission option";
                $oabbr = array();
                foreach ($user->conf->paper_opts->option_list() as $ox)
                    if ($ox !== $oex[1])
                        $oabbr[] = "“has:" . htmlspecialchars($ox->search_keyword()) . "”";
                if (count($oabbr))
                    $desc .= '<div class="hint">Other option ' . pluralx(count($oabbr), "search") . ': ' . commajoin($oabbr) . '</div>';
            } else if ($extype === "yes")
                $desc = "same meaning; abbreviations also accepted";
            else if ($extype === "numeric")
                $desc = "paper’s “" . htmlspecialchars($oex[1]->name) . "” option has value &gt; 100";
            else if ($extype === "selector")
                $desc = "paper’s “" . htmlspecialchars($oex[1]->name) . "” option has value “" . htmlspecialchars($oex[1]->selector[1]) . "”";
            else if ($extype === "attachment-count")
                $desc = "paper has more than 2 “" . htmlspecialchars($oex[1]->name) . "” attachments";
            else if ($extype === "attachment-filename")
                $desc = "paper has an “" . htmlspecialchars($oex[1]->name) . "” attachment with a .gif extension";
            else
                continue;
            echo $hth->search_trow($oex[0], $desc);
        }
    }

    echo $hth->tgroup("<a href=\"" . hoturl("help", "t=tags") . "\">Tags</a>");
    echo $hth->search_trow("#discuss", "tagged “discuss” (“tag:discuss” also works)");
    echo $hth->search_trow("-#discuss", "not tagged “discuss”");
    echo $hth->search_trow("order:discuss", "tagged “discuss”, sort by tag order (“rorder:” for reverse order)");
    echo $hth->search_trow("#disc*", "matches any tag that <em>starts with</em> “disc”");

    $cx = null;
    $cm = array();
    foreach ($user->conf->tags() as $t)
        foreach ($t->colors ? : array() as $c) {
            $cx = $cx ? : $c;
            if ($cx === $c)
                $cm[] = "“{$t->tag}”";
        }
    if (!empty($cm)) {
        array_unshift($cm, "“{$cx}”");
        echo $hth->search_trow("style:$cx", "tagged to appear $cx (tagged " . commajoin($cm, "or") . ")");
    }

    $roundname = meaningful_round_name($user);

    echo $hth->tgroup("Reviews");
    echo $hth->search_trow("re:me", "you are a reviewer");
    echo $hth->search_trow("re:fdabek", "“fdabek” in reviewer name/email");
    if ($retag)
        echo $hth->search_trow("re:#$retag", "has a reviewer tagged “#" . $retag . "”");
    echo $hth->search_trow("re:4", "four reviewers (assigned and/or completed)");
    if ($retag)
        echo $hth->search_trow("re:#$retag>1", "at least two reviewers (assigned and/or completed) tagged “#" . $retag . "”");
    echo $hth->search_trow("re:complete<3", "less than three completed reviews<br /><span class=\"hint\">Use “cre:<3” for short.</span>");
    echo $hth->search_trow("re:incomplete>0", "at least one incomplete review");
    echo $hth->search_trow("re:inprogress", "at least one in-progress review (started, but not completed)");
    echo $hth->search_trow("re:primary>=2", "at least two primary reviewers");
    echo $hth->search_trow("re:secondary", "at least one secondary reviewer");
    echo $hth->search_trow("re:external", "at least one external reviewer");
    echo $hth->search_trow("re:primary:fdabek:complete", "“fdabek” has completed a primary review");
    if ($roundname)
        echo $hth->search_trow("re:$roundname", "review in round “" . htmlspecialchars($roundname) . "”");
    echo $hth->search_trow("re:auwords<100", "has a review with less than 100 words in author-visible fields");
    if ($user->conf->setting("rev_tokens"))
        echo $hth->search_trow("retoken:J88ADNAB", "has a review with token J88ADNAB");
    if ($user->conf->setting("rev_ratings") != REV_RATINGS_NONE)
        echo $hth->search_trow("rate:+", "review was rated positively (“rate:-” and “rate:boring” also work; can combine with “re:”)");

    echo $hth->tgroup("Comments");
    echo $hth->search_trow("has:cmt", "at least one visible reviewer comment (not including authors’ response)");
    echo $hth->search_trow("cmt:>=3", "at least <em>three</em> visible reviewer comments");
    echo $hth->search_trow("has:aucmt", "at least one reviewer comment visible to authors");
    echo $hth->search_trow("cmt:sylvia", "“sylvia” (in name/email) wrote at least one visible comment; can combine with counts, use reviewer tags");
    $rnames = $user->conf->resp_round_list();
    if (count($rnames) > 1) {
        echo $hth->search_trow("has:response", "has an author’s response");
        echo $hth->search_trow("has:{$rnames[1]}response", "has $rnames[1] response");
    } else
        echo $hth->search_trow("has:response", "has author’s response");
    echo $hth->search_trow("anycmt:>1", "at least two visible comments, possibly <em>including</em> author’s response");

    echo $hth->tgroup("Leads");
    echo $hth->search_trow("lead:fdabek", "“fdabek” (in name/email) is discussion lead");
    echo $hth->search_trow("lead:none", "no assigned discussion lead");
    echo $hth->search_trow("lead:any", "some assigned discussion lead");
    echo $hth->tgroup("Shepherds");
    echo $hth->search_trow("shep:fdabek", "“fdabek” (in name/email) is shepherd (“none” and “any” also work)");
    echo $hth->tgroup("Conflicts");
    echo $hth->search_trow("conflict:me", "you have a conflict with the paper");
    echo $hth->search_trow("conflict:fdabek", "“fdabek” (in name/email) has a conflict with the paper<br /><span class='hint'>This search is only available to chairs and to PC members who can see the paper’s author list.</span>");
    echo $hth->search_trow("conflict:pc", "some PC member has a conflict with the paper");
    echo $hth->search_trow("conflict:pc>2", "at least three PC members have conflicts with the paper");
    echo $hth->search_trow("reconflict:\"1 2 3\"", "a reviewer of paper 1, 2, or 3 has a conflict with the paper");
    echo $hth->tgroup("Preferences");
    echo $hth->search_trow("pref:fdabek>0", "“fdabek” (in name/email) has preference &gt;&nbsp;0<br /><span class='hint'>PC members can search their own preferences; chairs can search anyone’s preferences.</span>");
    echo $hth->search_trow("pref:X", "a PC member’s preference has expertise “X” (expert)");
    echo $hth->tgroup("Status");
    echo $hth->search_trow(["q" => "status:sub", "t" => "all"], "paper is submitted for review");
    echo $hth->search_trow(["q" => "status:unsub", "t" => "all"], "paper is neither submitted nor withdrawn");
    echo $hth->search_trow(["q" => "status:withdrawn", "t" => "all"], "paper has been withdrawn");
    echo $hth->search_trow("has:final", "final copy uploaded");

    foreach ($user->conf->decision_map() as $dnum => $dname)
        if ($dnum)
            break;
    $qdname = strtolower($dname);
    if (strpos($qdname, " ") !== false)
        $qdname = "\"$qdname\"";
    echo $hth->tgroup("Decisions");
    echo $hth->search_trow("dec:$qdname", "decision is “" . htmlspecialchars($dname) . "” (partial matches OK)");
    echo $hth->search_trow("dec:yes", "one of the accept decisions");
    echo $hth->search_trow("dec:no", "one of the reject decisions");
    echo $hth->search_trow("dec:any", "decision specified");
    echo $hth->search_trow("dec:none", "decision unspecified");

    // find names of review fields to demonstrate syntax
    $farr = array(array(), array());
    foreach ($user->conf->all_review_fields() as $f)
        $farr[$f->has_options ? 0 : 1][] = $f;
    if (!empty($farr[0]) || !empty($farr[1]))
        echo $hth->tgroup("Review fields");
    if (count($farr[0])) {
        $r = $farr[0][0];
        echo $hth->search_trow("{$r->abbreviation1()}:{$r->typical_score()}", "at least one completed review has $r->name_html score {$r->typical_score()}");
        echo $hth->search_trow("{$r->search_keyword()}:{$r->typical_score()}", "other abbreviations accepted");
        if (count($farr[0]) > 1) {
            $r2 = $farr[0][1];
            echo $hth->search_trow(strtolower($r2->search_keyword()) . ":{$r2->typical_score()}", "other fields accepted (here, $r2->name_html)");
        }
        if (($range = $r->typical_score_range())) {
            echo $hth->search_trow("{$r->search_keyword()}:{$range[0]}..{$range[1]}", "completed reviews’ $r->name_html scores are in the {$range[0]}&ndash;{$range[1]} range<br /><small>(all scores between {$range[0]} and {$range[1]})</small>");
            $rt = $range[0] . ($r->option_letter ? "" : "-") . $range[1];
            echo $hth->search_trow("{$r->search_keyword()}:$rt", "completed reviews’ $r->name_html scores <em>fill</em> the {$range[0]}&ndash;{$range[1]} range<br /><small>(all scores between {$range[0]} and {$range[1]}, with at least one {$range[0]} and at least one {$range[1]})</small>");
        }
        if (!$r->option_letter)
            list($greater, $less, $hint) = array("greater", "less", "");
        else {
            $hint = "<br /><small>(better scores are closer to A than Z)</small>";
            if (opt("smartScoreCompare"))
                list($greater, $less) = array("better", "worse");
            else
                list($greater, $less) = array("worse", "better");
        }
        echo $hth->search_trow("{$r->search_keyword()}:>{$r->typical_score()}", "at least one completed review has $r->name_html score $greater than {$r->typical_score()}" . $hint);
        echo $hth->search_trow("{$r->search_keyword()}:2<={$r->typical_score()}", "at least two completed reviews have $r->name_html score $less than or equal to {$r->typical_score()}");
        if ($roundname)
            echo $hth->search_trow("{$r->search_keyword()}:$roundname>{$r->typical_score()}", "at least one completed review in round " . htmlspecialchars($roundname) . " has $r->name_html score $greater than {$r->typical_score()}");
        echo $hth->search_trow("{$r->search_keyword()}:ext>{$r->typical_score()}", "at least one completed external review has $r->name_html score $greater than {$r->typical_score()}");
        echo $hth->search_trow("{$r->search_keyword()}:pc:2>{$r->typical_score()}", "at least two completed PC reviews have $r->name_html score $greater than {$r->typical_score()}");
        echo $hth->search_trow("{$r->search_keyword()}:sylvia={$r->typical_score()}", "“sylvia” (reviewer name/email) gave $r->name_html score {$r->typical_score()}");
        $t = "";
    }
    if (count($farr[1])) {
        $r = $farr[1][0];
        echo $hth->search_trow($r->abbreviation1() . ":finger", "at least one completed review has “finger” in the $r->name_html field");
        echo $hth->search_trow("{$r->search_keyword()}:finger", "other abbreviations accepted");
        echo $hth->search_trow("{$r->search_keyword()}:any", "at least one completed review has text in the $r->name_html field");
    }

    if (count($farr[0])) {
        $r = $farr[0][0];
        echo $hth->tgroup("<a href=\"" . hoturl("help", "t=formulas") . "\">Formulas</a>");
        echo $hth->search_trow("formula:all({$r->search_keyword()}={$r->typical_score()})",
            "all reviews have $r->name_html score {$r->typical_score()}<br />"
            . "<span class='hint'><a href=\"" . hoturl("help", "t=formulas") . "\">Formulas</a> can express complex numerical queries across review scores and preferences.</span>");
        echo $hth->search_trow("f:all({$r->search_keyword()}={$r->typical_score()})", "“f” is shorthand for “formula”");
        echo $hth->search_trow("formula:var({$r->search_keyword()})>0.5", "variance in {$r->search_keyword()} is above 0.5");
        echo $hth->search_trow("formula:any({$r->search_keyword()}={$r->typical_score()} && pref<0)", "at least one reviewer had $r->name_html score {$r->typical_score()} and review preference &lt; 0");
    }

    echo $hth->tgroup("Display");
    echo $hth->search_trow("show:tags show:conflicts", "show tags and PC conflicts in the results");
    echo $hth->search_trow("hide:title", "hide title in the results");
    if (count($farr[0])) {
        $r = $farr[0][0];
        echo $hth->search_trow("show:max({$r->search_keyword()})", "show a <a href=\"" . hoturl("help", "t=formulas") . "\">formula</a>");
        echo $hth->search_trow("sort:{$r->search_keyword()}", "sort by score");
        echo $hth->search_trow("sort:\"{$r->search_keyword()} variance\"", "sort by score variance");
    }
    echo $hth->search_trow("sort:-status", "sort by reverse status");
    echo $hth->search_trow("edit:#discuss", "edit the values for tag “#discuss”");
    echo $hth->search_trow("search1 THEN search2", "like “search1 OR search2”, but papers matching “search1” are grouped together and appear earlier in the sorting order");
    echo $hth->search_trow("1-5 THEN 6-10 show:compact", "display searches in compact columns");
    echo $hth->search_trow("search1 HIGHLIGHT search2", "search for “search1”, but <span class=\"taghl highlightmark\">highlight</span> papers in that list that match “search2” (also try HIGHLIGHT:pink, HIGHLIGHT:green, HIGHLIGHT:blue)");

    echo $hth->end_table();
}

function _current_tag_list(Contact $user, $property) {
    $ct = $user->conf->tags()->filter($property);
    return empty($ct) ? "" : " (currently "
            . join(", ", array_map(function ($t) { return "“" . Ht::link($t->tag, hoturl("search", "q=%23{$t->tag}")) . "”"; }, $ct))
            . ")";
}

function _singleVoteTag(Contact $user) {
    $vt = $user->conf->tags()->filter("vote");
    return empty($vt) ? "vote" : current($vt)->tag;
}

function tracks(Contact $user, $hth) {
    echo "
<p>Tracks give you fine-grained control over PC member rights. With tracks, PC
members can have different rights to see and review papers, depending on the
papers’ " . Ht::link("tags", hoturl("help", "t=tags")) . ".</p>

<p>Set up tracks on the <a href=\"" . hoturl("settings", "group=tracks") . "\">Settings &gt;
Tracks</a> page.</p>";

    echo $hth->subhead("Example: External review committee");
    echo "
<p>An <em>external review committee</em> is a subset of the PC that may bid on
papers to review, and may be assigned reviews (using, for example, the
<a href=\"" . hoturl("autoassign") . "\">autoassignment tool</a>), but may not
self-assign reviews, and may not view reviews except for papers they have
reviewed. To set this up:</p>

<ul>
<li>Give external review committee members the “erc” tag.</li>
<li>On Settings &gt; Tracks, “For papers not on other
tracks,” select “Who can see reviews? &gt; PC members without tag: erc”
and “Who can self-assign a review? &gt; PC members without tag: erc”.</li>
</ul>";

    echo $hth->subhead("Example: PC-paper review committee");
    echo "
<p>A <em>PC-paper review committee</em> is a subset of the PC that reviews papers
with PC coauthors. PC-paper review committees are kept separate from the main
PC; they only bid on and review PC papers, while the main PC handles all other
papers. To set this up:</p>

<ul>
<li>Give PC-paper review committee members the “pcrc” tag.</li>
<li>Give PC papers the “pcrc” tag.</li>
<li>On Settings &gt; Tracks, add a track for tag “pcrc” and
  select “Who can see these papers? &gt; PC members with tag: pcrc”.
  (Users who can’t see a paper also can’t review it,
  so there’s no need to explicitly set the other permissions.)</li>
<li>For papers not on other tracks, select “Who can see these papers? &gt; PC
  members without tag: pcrc”.</li>

</ul>";

    echo $hth->subhead("Example: Track chair");
    echo "
<p>A <em>track chair</em> is a PC member with full administrative
rights over a subset of papers. To set this up for, say, an “industrial”
track:</p>

<ul>
<li>Give the industrial track chair(s) the “industrial-chair” tag.</li>
<li>Give industrial-track papers the “industrial” tag.</li>
<li>On Settings &gt; Tracks, add a track for tag “industrial”,
  and select “Who can administer these papers? &gt; PC members with tag:
  industrial-chair”.</li>
</ul>

<p>A track chair can run the autoassigner, make assignments, edit papers, and
generally administer all papers on their tracks. Track chairs cannot modify
site settings or change track tags, however.</p>";

    echo $hth->subhead("Understanding permissions");
    echo "<p>Tracks restrict permissions.
For example, when
the “PC members can review <strong>any</strong> submitted paper”
setting is off, <em>no</em> PC member can enter an unassigned review,
no matter what the track settings say.
It can be useful to “act as” a member of the PC to check which permissions
are actually in effect.</p>";
}



function revround(Contact $user, $hth) {
    echo "
<p>Many conferences divide their review assignments into multiple <em>rounds</em>.
Each round is given a name, such as “R1” or “lastround”
(we suggest very short names like “R1”).
Configure rounds on the <a href='" . hoturl("settings", "group=reviews#rounds")
. "'>settings page</a>.
To search for any paper with a round “R2” review assignment, <a href='" . hoturl("search", "q=re:R2") . "'>search for re:R2”</a>.
To list a PC member’s round “R1” review assignments, <a href='" . hoturl("search", "q=re:membername:R1") . "'>search for “re:membername:R1”</a>.</p>

<p>Different rounds usually share the same review form, but you can also
mark review fields as appearing only in certain rounds. First configure
rounds, then see
<a href=\"" . hoturl("settings", "group=reviewform") . "\">Settings &gt; Review form</a>.</p>";

    echo $hth->subhead("Assigning rounds");
    echo "
<p>New assignments are marked by default with the round defined in
<a href='" . hoturl("settings", "group=reviews#rounds") . "'>review settings</a>.
The automatic and bulk assignment pages also let you set a review round.</p>";

    // get current tag settings
    if ($user->isPC) {
        $texts = array();
        if (($rr = $user->conf->assignment_round_name(false))) {
            $texts[] = "The review round for new assignments is “<a href=\""
                . hoturl("search", "q=round%3A" . urlencode($rr))
                . "\">" . htmlspecialchars($rr) . "</a>”";
            if ($user->privChair)
                $texts[0] .= " (use <a href=\"" . hoturl("settings", "group=reviews#rounds") . "\">Settings &gt; Reviews</a> to change this).";
            else
                $texts[0] .= ".";
        }
        $rounds = array();
        if ($user->conf->has_rounds()) {
            $result = $user->conf->qe("select distinct reviewRound from PaperReview");
            while (($row = edb_row($result)))
                if ($row[0] && ($rname = $user->conf->round_name($row[0])))
                    $rounds[] = "“<a href=\""
                        . hoturl("search", "q=round%3A" . urlencode($rname))
                        . "\">" . htmlspecialchars($rname) . "</a>”";
            sort($rounds);
        }
        if (count($rounds))
            $texts[] = "Review rounds currently in use: " . commajoin($rounds) . ".";
        else if (!count($texts))
            $texts[] = "So far no review rounds have been defined.";
        echo $hth->subhead("Round status");
        echo "<p>", join(" ", $texts), "</p>\n";
    }
}


function revrate(Contact $user, $hth) {
    echo "<p>PC members and, optionally, external reviewers can rate one another’s
reviews.  We hope this feedback will help reviewers improve the quality of
their reviews.  The interface appears above each visible review:</p>

<p><div class='rev_rating'>
  How helpful is this review? &nbsp;<form class><div class=\"inline\">"
                  . Ht::select("rating", ReviewForm::$rating_types, "n")
                  . "</div></form>
</div></p>

<p>When rating a review, please consider its value for both the program
  committee and the authors.  Helpful reviews are specific, clear, technically
  focused, and, when possible, provide direction for the authors’ future work.
  The rating options are:</p>

<dl>
<dt><strong>Average</strong></dt>
<dd>The review has acceptable quality.  This is the default, and should be
  used for most reviews.</dd>
<dt><strong>Very helpful</strong></dt>
<dd>Great review.  Thorough, clear, constructive, and gives
  good ideas for next steps.</dd>
<dt><strong>Too short</strong></dt>
<dd>The review is incomplete or too terse.</dd>
<dt><strong>Too vague</strong></dt>
<dd>The review’s arguments are weak, mushy, or otherwise technically
  unconvincing.</dd>
<dt><strong>Too narrow</strong></dt>
<dd>The review’s perspective seems limited; for instance, it might
  overly privilege the reviewer’s own work.</dd>
<dt><strong>Not constructive</strong></dt>
<dd>The review’s tone is unnecessarily aggressive or gives little useful
  direction.</dd>
<dt><strong>Not correct</strong></dt>
<dd>The review misunderstands the paper.</dd>
</dl>

<p>HotCRP reports the numbers of non-average ratings for each review.
  It does not report who gave the ratings, and it
  never shows rating counts to authors.</p>

<p>To find which of your reviews might need work, simply
<a href='" . hoturl("search", "q=rate:-") . "'>search for “rate:&minus;”</a>.
To find all reviews with positive ratings,
<a href='" . hoturl("search", "q=re:any+rate:%2B") . "'>search for “re:any&nbsp;rate:+”</a>.
You may also search for reviews with specific ratings; for instance,
<a href='" . hoturl("search", "q=rate:helpful") . "'>search for “rate:helpful”</a>.</p>";

    if ($user->conf->setting("rev_ratings") == REV_RATINGS_PC)
        $what = "only PC members";
    else if ($user->conf->setting("rev_ratings") == REV_RATINGS_PC_EXTERNAL)
        $what = "PC members and external reviewers";
    else
        $what = "no one";
    echo $hth->subhead("Settings");
    echo "<p>Chairs set how ratings work on the <a href=\"" . hoturl("settings", "group=reviews") . "\">review settings
page</a>.", ($user->is_reviewer() ? " Currently, $what can rate reviews." : ""), "</p>";

    echo $hth->subhead("Visibility");
    echo "<p>A review’s ratings are visible to any unconflicted PC members who can see
the review, but HotCRP tries to hide ratings from review authors if they
could figure out who assigned the rating: if only one PC member could
rate a review, then that PC member’s rating is hidden from the review
author.</p>";
}


function scoresort(Contact $user, $hth) {
    echo "
<p>Some paper search results include columns with score graphs. Click on a score
column heading to sort the paper list using that score. Search &gt; View
options changes how scores are sorted.  There are five choices:</p>

<dl>

<dt><strong>Counts</strong> (default)</dt>

<dd>Sort by the number of highest scores, then the number of second-highest
scores, then the number of third-highest scores, and so on.  To sort a paper
with fewer reviews than others, HotCRP adds phantom reviews with scores just
below the paper’s lowest real score.  Also known as Minshall score.</dd>

<dt><strong>Average</strong></dt>
<dd>Sort by the average (mean) score.</dd>

<dt><strong>Median</strong></dt>
<dd>Sort by the median score.</dd>

<dt><strong>Variance</strong></dt>
<dd>Sort by the variance in scores.</dd>

<dt><strong>Max &minus; min</strong></dt>
<dd>Sort by the difference between the largest and smallest scores (a good
measure of differences of opinion).</dd>

<dt><strong>My score</strong></dt>
<dd>Sort by your score.  In the score graphs, your score is highlighted with a
darker colored square.</dd>

</dl>";
}


function showvotetags(Contact $user, $hth) {
    $votetag = _singleVoteTag($user);
    echo "<p>Some conferences have PC members vote for papers.
Each PC member is assigned a vote allotment, and can distribute that allotment
arbitrarily among unconflicted papers.
Alternately, each PC member can vote, once, for as many papers as they like (“approval voting”).
The PC’s aggregated vote totals might help determine
which papers to discuss.</p>

<p>HotCRP supports voting through the <a href='" . hoturl("help", "t=tags") . "'>tags system</a>.
The chair can <a href='" . hoturl("settings", "group=tags") . "'>define a set of voting tags</a> and allotments" . _current_tag_list($user, "vote") . ".
PC members vote by assigning the corresponding twiddle tags;
the aggregated PC vote is visible in the public tag.</p>

<p>For example, assume that an administrator defines a voting tag
 “". $votetag . "” with an allotment of 10.
To use two votes for a paper, a PC member tags the paper as
“~". $votetag . "#2”. The system
automatically adds the tag “". $votetag . "#2” to that
paper (note the
lack of the “~”), indicating that the paper has two total votes.
As other PC members add their votes with their own “~” tags, the system
updates the main tag to reflect the total.
(The system ensures no PC member exceeds their allotment.) </p>

<p>
To see the current voting status, search by
<a href=\"" . hoturl("search", "q=rorder:" . $votetag . "") . "\">
rorder:". $votetag . "</a>. Use view options to show tags
in the search results (or set up a
<a href='" . hoturl("help", "t=formulas") . "'>formula</a>).
</p>

<p>
Hover to learn how the PC voted:</p>

<p>" . Ht::img("extagvotehover.png", "[Hovering over a voting tag]", ["width" => 390, "height" => 46]) . "</p>";
}


function showranking(Contact $user, $hth) {
    echo "<p>Paper ranking is a way to extract the PC’s preference order for
submitted papers.  Each PC member ranks the submitted papers, and a voting
algorithm, <a href='http://en.wikipedia.org/wiki/Schulze_method'>the Schulze
method</a> by default, combines these rankings into a global preference order.</p>

<p>HotCRP supports ranking through <a
href='" . hoturl("help", "t=tags") . "'>tags</a>.  The chair chooses
a tag for ranking—“rank” is a good default—and enters it on <a
href='" . hoturl("settings", "group=tags") . "'>the settings page</a>.
PC members then rank papers using their private versions of this tag,
tagging their first preference with “~rank#1”,
their second preference with “~rank#2”,
and so forth.  To combine PC rankings into a global preference order, the PC
chair selects all papers on the <a href='" . hoturl("search", "q=") . "'>search page</a>
and chooses Tags &gt; Calculate&nbsp;rank, entering
“rank” for the tag.  At that point, the global rank can be viewed
by a <a href='" . hoturl("search", "q=order:rank") . "'>search for
“order:rank”</a>.</p>

<p>PC members can enter rankings by reordering rows in a paper list.
For example, for rank tag “rank”, PC members should
<a href=\"" . hoturl("search", "q=editsort%3A%23~rank") . "\">search for “editsort:#~rank”</a>.
Ranks can be entered directly in the text fields, or the rows can be dragged
into position using the dotted areas on the right-hand side of the list.</p>

<p>Alternately, PC members can use an <a href='" . hoturl("offline") . "'>offline
ranking form</a>. Download a ranking file, rearrange the lines to create a
rank, and upload the form again.  For example, here is an initial ranking
file:</p>

<pre class='entryexample'>
# Edit the rank order by rearranging this file's lines.
# The first line has the highest rank.

# Lines that start with \"#\" are ignored.  Unranked papers appear at the end
# in lines starting with \"X\", sorted by overall merit.  Create a rank by
# removing the \"X\"s and rearranging the lines.  A line that starts with \"=\"
# marks a paper with the same rank as the preceding paper.  A line that starts
# with \">>\", \">>>\", and so forth indicates a rank gap between the preceding
# paper and the current paper.  When you are done, upload the file at
#   http://your.site.here.com/offline

Tag: ~rank


X	1	Write-Back Caches Considered Harmful
X	2	Deconstructing Suffix Trees
X	4	Deploying Congestion Control Using Homogeneous Modalities
X	5	The Effect of Collaborative Epistemologies on Theory
X	6	The Influence of Probabilistic Methodologies on Networking
X	8	Rooter: A Methodology for the Typical Unification of Access Points and Redundancy
X	10	Decoupling Lambda Calculus from 802.11 Mesh Networks in Moore's Law
X	11	Analyzing Scatter/Gather I/O Using Encrypted Epistemologies
</pre>

<p>The user might edit the file as follows:</p>

<pre class='entryexample'>
	8	Rooter: A Methodology for the Typical Unification of Access Points and Redundancy
	5	The Effect of Collaborative Epistemologies on Theory
=	1	Write-Back Caches Considered Harmful
	2	Deconstructing Suffix Trees
>>	4	Deploying Congestion Control Using Homogeneous Modalities

X	6	The Influence of Probabilistic Methodologies on Networking
X	10	Decoupling Lambda Calculus from 802.11 Mesh Networks in Moore's Law
X	11	Analyzing Scatter/Gather I/O Using Encrypted Epistemologies
</pre>

<p>Uploading this file produces the following ranking:</p>

<p><table><tr><th class='pad'>ID</th><th>Title</th><th>Rank tag</th></tr>
<tr><td class='pad'>#8</td><td class='pad'>Rooter: A Methodology for the Typical Unification of Access Points and Redundancy</td><td class='pad'>~rank#1</td></tr>
<tr><td class='pad'>#5</td><td class='pad'>The Effect of Collaborative Epistemologies on Theory</td><td class='pad'>~rank#2</td></tr>
<tr><td class='pad'>#1</td><td class='pad'>Write-Back Caches Considered Harmful</td><td class='pad'>~rank#2</td></tr>
<tr><td class='pad'>#2</td><td class='pad'>Deconstructing Suffix Trees</td><td class='pad'>~rank#3</td></tr>
<tr><td class='pad'>#4</td><td class='pad'>Deploying Congestion Control Using Homogeneous Modalities</td><td class='pad'>~rank#5</td></tr></table></p>

<p>Since #6, #10, and #11 still had X prefixes, they were not assigned a rank.
 Searching for “order:~rank” returns the user’s personal ranking;
 administrators can search for
 “order:<i>pcname</i>~rank” to see a PC member’s ranking.
 Once a global ranking is assigned, “order:rank” will show it.</p>";
}


function showformulas(Contact $user, $hth) {
    echo "<p>Program committee members and administrators can search and display <em>formulas</em>
that calculate properties of paper scores&mdash;for instance, the
standard deviation of papers’ Overall merit scores, or average Overall
merit among reviewers with high Reviewer expertise.</p>

<p>To display a formula, use a search term such as “<a href=\""
             . hoturl("search", "q=show%3avar%28OveMer%29") . "\">show:var(OveMer)</a>” (show
the variance in Overall merit scores, along with statistics for all papers).
You can also <a href=\"" . hoturl("graph", "g=formula") . "\">graph formulas</a>.
To search for a formula, use a search term such as “<a href=\""
             . hoturl("search", "q=formula%3avar%28OveMer%29%3e0.5") . "\">formula:var(OveMer)>0.5</a>”
(select papers with variance in Overall merit greater than 0.5).
Or save formulas using <a
href=\"" . hoturl("search", "q=&amp;tab=formulas") . "\">Search &gt; View options
&gt; Edit formulas</a>.</p>

<p>Formulas use a familiar expression language.
For example, this computes the sum of the squares of the overall merit scores:</p>

<blockquote>sum(OveMer*OveMer)</blockquote>

<p>This calculates an average of overall merit scores, weighted by expertise
(high-expertise reviews are given slightly more weight):</p>

<blockquote>wavg(OveMer, RevExp >= 4 ? 1 : 0.8)</blockquote>

<p>And there are many variations. This version gives more weight to PC
reviewers with the “#heavy” tag:</p>

<blockquote>wavg(OveMer, re:#heavy + 1)</blockquote>

<p>(“re:#heavy + 1” equals 2 for #heavy reviews and 1 for others.)</p>

<p>Formulas work better for numeric scores, but you can use them for letter
scores too. HotCRP uses alphabetical order for letter scores, so the “min” of
scores A, B, and D is A. For instance:</p>

<blockquote>count(confidence=X)</blockquote>";

    echo $hth->subhead("Expressions");
    echo "<p>Formula expressions are built from the following parts:</p>";
    echo $hth->table();
    echo $hth->tgroup("Arithmetic");
    echo $hth->trow("2", "Numbers");
    echo $hth->trow("true, false", "Booleans");
    echo $hth->trow("<em>e</em> + <em>e</em>, <em>e</em> - <em>e</em>", "Addition, subtraction");
    echo $hth->trow("<em>e</em> * <em>e</em>, <em>e</em> / <em>e</em>, <em>e</em> % <em>e</em>", "Multiplication, division, remainder");
    echo $hth->trow("<em>e</em> ** <em>e</em>", "Exponentiation");
    echo $hth->trow("<em>e</em> == <em>e</em>, <em>e</em> != <em>e</em>,<br /><em>e</em> &lt; <em>e</em>, <em>e</em> &gt; <em>e</em>, <em>e</em> &lt;= <em>e</em>, <em>e</em> &gt;= <em>e</em>", "Comparisons");
    echo $hth->trow("!<em>e</em>", "Logical not");
    echo $hth->trow("<em>e1</em> &amp;&amp; <em>e2</em>", "Logical and (returns <em>e1</em> if <em>e1</em> is false, otherwise returns <em>e2</em>)");
    echo $hth->trow("<em>e1</em> || <em>e2</em>", "Logical or (returns <em>e1</em> if <em>e1</em> is true, otherwise returns <em>e2</em>)");
    echo $hth->trow("<em>test</em> ? <em>iftrue</em> : <em>iffalse</em>", "If-then-else operator");
    echo $hth->trow("(<em>e</em>)", "Parentheses");
    echo $hth->trow("greatest(<em>e</em>, <em>e</em>, ...)", "Maximum");
    echo $hth->trow("least(<em>e</em>, <em>e</em>, ...)", "Minimum");
    echo $hth->trow("log(<em>e</em>)", "Natural logarithm");
    echo $hth->trow("log(<em>e</em>, <em>b</em>)", "Log to the base <em>b</em>");
    echo $hth->trow("round(<em>e</em>[, <em>m</em>])", "Round to the nearest multiple of <em>m</em>");
    echo $hth->trow("null", "The null value");
    echo $hth->tgroup("Submission properties");
    echo $hth->trow("pid", "Paper ID");
    echo $hth->trow("au", "Number of authors");
    echo $hth->trow("au:pc", "Number of PC authors");
    echo $hth->trow("au:<em>text</em>", "Number of authors matching <em>text</em>");
    echo $hth->tgroup("Tags");
    echo $hth->trow("#<em>tagname</em>", "True if this paper has tag <em>tagname</em>");
    echo $hth->trow("tagval:<em>tagname</em>", "The value of tag <em>tagname</em>, or null if this paper doesn’t have that tag");
    echo $hth->tgroup("Scores");
    echo $hth->trow("overall-merit", "This review’s Overall merit score<div class=\"hint\">Only completed reviews are considered.</div>");
    echo $hth->trow("OveMer", "Abbreviations also accepted");
    echo $hth->trow("OveMer:external", "Overall merit for external reviews, null for other reviews");
    echo $hth->trow("OveMer:R2", "Overall merit for round R2 reviews, null for other reviews");
    echo $hth->tgroup("Submitted reviews");
    echo $hth->trow("re:type", "Review type");
    echo $hth->trow("re:round", "Review round");
    echo $hth->trow("re:auwords", "Review word count (author-visible fields only)");
    echo $hth->trow("re:primary", "True for primary reviews");
    echo $hth->trow("re:secondary", "True for secondary reviews");
    echo $hth->trow("re:external", "True for external reviews");
    echo $hth->trow("re:pc", "True for PC reviews");
    echo $hth->trow("re:sylvia", "True if reviewer matches “sylvia”");
    if (($retag = meaningful_pc_tag($user)))
        echo $hth->trow("re:#$retag", "True if reviewer has tag “#{$retag}”");
    echo $hth->tgroup("Review preferences");
    echo $hth->trow("pref", "Review preference");
    echo $hth->trow("prefexp", "Predicted expertise");
    echo $hth->end_table();

    echo $hth->subhead("Aggregate functions");
    echo "<p>Aggregate functions calculate a
value based on all of a paper’s submitted reviews and/or review preferences.
For instance, “max(OveMer)” would return the maximum Overall merit score
assigned to a paper.</p>

<p>An aggregate function’s argument is calculated once per visible review
or preference.
For instance, “max(OveMer/RevExp)” calculates the maximum value of
“OveMer/RevExp” for any review, whereas
“max(OveMer)/max(RevExp)” divides the maximum overall merit by the
maximum reviewer expertise.</p>

<p>The top-level value of a formula expression cannot be a raw review score
or preference.
Use an aggregate function to calculate a property over all review scores.</p>";
    echo $hth->table();
    echo $hth->tgroup("Aggregates");
    echo $hth->trow("max(<em>e</em>), min(<em>e</em>)", "Maximum, minimum");
    echo $hth->trow("count(<em>e</em>)", "Number of reviews where <em>e</em> is not null or false");
    echo $hth->trow("sum(<em>e</em>)", "Sum");
    echo $hth->trow("avg(<em>e</em>)", "Average (mean)");
    echo $hth->trow("wavg(<em>e</em>, <em>weight</em>)", "Weighted average; equals “sum(<em>e</em> * <em>weight</em>) / sum(<em>weight</em>)”");
    echo $hth->trow("median(<em>e</em>)", "Median");
    echo $hth->trow("quantile(<em>e</em>, <em>p</em>)", "Quantile; 0≤<em>p</em>≤1; 0 yields min, 0.5 median, 1 max");
    echo $hth->trow("stddev(<em>e</em>)", "Population standard deviation");
    echo $hth->trow("var(<em>e</em>)", "Population variance");
    echo $hth->trow("stddev_samp(<em>e</em>), var_samp(<em>e</em>)", "Sample standard deviation, sample variance");
    echo $hth->trow("any(<em>e</em>)", "True if any of the reviews have <em>e</em> true");
    echo $hth->trow("all(<em>e</em>)", "True if all of the reviews have <em>e</em> true");
    echo $hth->trow("argmin(<em>x</em>, <em>e</em>)", "Value of <em>x</em> when <em>e</em> is minimized");
    echo $hth->trow("argmax(<em>x</em>, <em>e</em>)", "Value of <em>x</em> when <em>e</em> is maximized");
    echo $hth->trow("my(<em>e</em>)", "Calculate <em>e</em> for your review");
    echo $hth->end_table();

}


function chair(Contact $user, $hth) {
    echo $hth->subhead("Submission time");
    echo "
<p>Follow these steps to prepare to accept paper submissions.</p>

<ol>

<li><p><strong><a href='" . hoturl("settings", "group=users") . "'>Set up PC
  member accounts</a></strong>. Many PCs are divided into classes, such as
  “heavy” and “light”, or “PC” and “ERC”. Mark these classes with user tags.
  It’s also useful to configure <a href='" . hoturl("settings",
  "group=tags") . "'>tag colors</a> so that PC member names are displayed
  differently based on class (for instance, heavy PC member names might appear
  in <b>bold</b>).</p></li>

<li><p><strong><a href='" . hoturl("settings", "group=sub") . "'>Set submission
  policies</a></strong>, including whether submission is blind.</p></li>

<li><p><strong><a href='" . hoturl("settings", "group=sub") . "'>Set submission
  deadlines.</a></strong> Authors first <em>register</em>, then <em>submit</em>
  their papers, possibly multiple times; they choose for each submitted
  version whether that version is ready for review.  Normally, HotCRP allows
  authors to update their papers until the deadline, but you can also require
  that authors “freeze” each submission explicitly; only
  administrators can update frozen submissions.
  The only deadline that really matters is the paper submission
  deadline, but HotCRP also supports a separate paper registration deadline,
  which will force authors to register a few days before they submit.  An
  optional <em>grace period</em> applies to both deadlines:
  HotCRP reports the deadlines, but allows submissions and updates post-deadline
  for the specified grace period.  This provides some
  protection against last-minute server overload and gives authors
  some slack.</p></li>

<li><p><strong><a href='" . hoturl("settings", "group=subform") . "'>Set up
  the submission form</a></strong>, including whether abstracts are required,
  whether authors check off conflicted PC members (“Collect authors’ PC
  conflicts with checkboxes”), and whether authors must enter additional
  non-PC collaborators, which can help detect conflicts with external
  reviewers (“Collect authors’ other collaborators as text”). The submission
  form also can include:</p>

  <ul>

  <li><p><strong>PDF format checker.</strong> This adds a “Check format” link
  to the Edit Paper screen. Clicking the link checks the paper for formatting
  errors, such as going over the page limit.  Papers with formatting errors
  may still be submitted, since the checker itself can make mistakes, but the
  automated checker leaves cheating authors no excuse.</p></li>

  <li><p><strong>Options</strong> such as checkboxes, selectors, freeform
  text, and uploaded attachments. Checkbox options might include “Consider
  this paper for the Best Student Paper award” or “Provide this paper to the
  European shadow PC.” Attachment options might include supplemental material.
  You can <a href='" . hoturl("search") . "'>search</a> for papers with or
  without each option.</p></li>

  <li><p><strong>Topics.</strong> Authors can select topics, such as
  “Applications” or “Network databases,” that characterize their paper’s
  subject areas.  PC members express topics for which they have high, medium,
  and low interest, improving automatic paper assignment.  Although explicit
  preferences (see below) are better than topic-based assignments, busy PC
  members might not specify their preferences; topic matching lets you do a
  reasonable job at assigning papers anyway.</p></li>

  </ul></li>

<li><p>Take a look at a <a href='" . hoturl("paper", "p=new") . "'>paper
  submission page</a> to make sure it looks right.</p></li>

<li><p><strong><a href='" . hoturl("settings", "group=sub") . "'>Open the site
  for submissions.</a></strong> Submissions will be accepted only until the
  listed deadline.</p></li>

</ol>";

    echo $hth->subhead("Assignments");
    echo "
<p>After the submission deadline has passed:</p>

<ol>

<li><p>Consider checking <a
  href='" . hoturl("search", "q=&amp;t=all") . "'>the papers</a> for
  anomalies.  Withdraw and/or delete duplicates or update details on the <a
  href='" . hoturl("paper") . "'>paper pages</a> (via “Edit paper”).
  Also consider contacting the authors of <a
  href='" . hoturl("search", "q=status:unsub&amp;t=all") . "'>papers that
  were never officially submitted</a>, especially if a PDF document was
  uploaded; sometimes a
  user will uncheck “The paper is ready for review” by mistake.</p></li>

<li><p><strong>Check for formatting violations (optional).</strong> <a href='" . hoturl("search", "q=") . "'>Search</a>
  &gt; Download &gt; Format check will download a summary report. Serious errors
  are also shown on paper pages (problematic PDFs are distinguished by an
  “X”).</p></li>

<li><p><strong><a href='" . hoturl("settings", "group=reviewform") . "'>Prepare the
  review form.</a></strong> Take a look at the templates to get
  ideas.</p></li>

<li><p><strong><a href='" . hoturl("settings", "group=reviews") . "'>Set review
  policies and deadlines</a></strong>, including reviewing deadlines, whether
  review is blind, and whether PC members may review any paper
  (usually “yes” is the right answer).</p></li>

<li><p><strong><a href='" . hoturl("help", "t=tracks") . "'>Prepare tracks
  (optional).</a></strong> Tracks give chairs fine-grained control over PC
  members’ access rights for individual papers. Example situations calling for
  tracks include external review committees, PC-paper review committees, and
  multi-track conferences.</li>

<li><p><strong><a href='" . hoturl("reviewprefs") . "'>Collect review
  preferences from the PC.</a></strong> PC members can rank-order papers they
  want or don’t want to review.  They can either set their preferences <a
  href='" . hoturl("reviewprefs") . "'>all at once</a>, or (often more
  convenient) page through the <a
  href='" . hoturl("search", "q=&amp;t=s") . "'>list of submitted papers</a>
  setting their preferences on the <a
  href='" . hoturl("paper") . "'>paper pages</a>.</p>

  <p>If you’d like, you can collect review preferences before the submission
  deadline.  Select <a href='" . hoturl("settings", "group=sub") . "'>“PC can
  see <em>all registered papers</em> until submission deadline”</a>, which
  allows PC members to see abstracts for registered papers that haven’t yet
  been submitted.</p></li>

<li><p><strong><a href='" . hoturl("manualassign", "kind=c") . "'>Assign
  conflicts.</a></strong> HotCRP automatically installs the authors’ declared
  conflicts. HotCRP <i>does not</i> automatically install other conflicts, such
  as conflicts indicated by PC members’ “Collaborators and other affiliations”
  or their review preferences. Use <a href='" .
  hoturl("manualassign", "kind=c") . "'>the manual assignment tool</a> to
  search for potential missing conflicts, and use <a href='" .
  hoturl("autoassign", "a=prefconflict") . "'>the automatic assigner</a>
  to assign conflicts when PC members have entered preferences of &minus;100
  or less.</p></li>

<li><p><strong><a href='" . hoturl("manualassign") . "'>Assign
  reviews.</a></strong> You can make assignments <a
  href='" . hoturl("assign") . "'>by paper</a>, <a
  href='" . hoturl("manualassign") . "'>by PC member</a>, <a
  href='" . hoturl("bulkassign") . "'>by uploading an assignments
  file</a>, or, even easier, <a
  href='" . hoturl("autoassign") . "'>automatically</a>.  PC
  review assignments can be “primary” or “secondary”; the difference is
  that primary reviewers are expected to complete their review, but a
  secondary reviewer can delegate their review to someone else. You can
  also assign PC “metareviews”. Unlike normal reviewers, a metareviewer can
  view all other reviews before submitting their own.</p>

  <p>The default assignments pages apply to all submitted papers.  You can
  also assign subsets of papers obtained through <a
  href='" . hoturl("help", "t=search") . "'>search</a>, such as <a
  href='" . hoturl("search", "q=cre:%3C3&amp;t=s") . "'>papers
  with fewer than three completed reviews</a>.</p></li>

<li><p><strong><a href='" . hoturl("settings", "group=reviews") . "'>Open the site
  for reviewing.</a></strong></p></li>

</ol>";


    echo $hth->subhead("Chair conflicts");
    echo "
<p>Chairs and system administrators can access any information stored in the
conference system, including reviewer identities for conflicted papers.
It is easiest to simply accept such conflicts as a fact of life. Chairs
who can’t handle conflicts fairly shouldn’t be chairs. However, HotCRP
does offer other mechanisms for conflicted reviews.</p>

<p>The key step is to pick a PC member to manage the reviewing and
discussion process for the relevant papers. This PC member is called the
<em>paper administrator</em>. Use the left-hand side of the
<a href='" . hoturl("assign") . "'>paper assignment pages</a> to enter paper administrators. (You may need to
“Override conflicts” to access the assignment page.)
A paper’s administrators have full privilege to assign and view reviews
for that paper, although they cannot change conference settings.</p>

<p>Assigned administrators change conflicted chairs’
access rights. Normally, a conflicted chair can easily override
their conflict. If a paper has an administrator, however, conflicts cannot
be overridden until the administrator is removed.</p>

<p>Paper administrators make life easy for PC reviewers while hiding
conflicts from chairs in most circumstances.
However, determined chairs can still discover reviewer identities
via HotCRP logs, review counts, and mails (and, of course,
by removing the administrator).
For additional privacy, a conference can use
<em>review tokens</em>, which are completely anonymous
review slots. To create a token, an administrator
goes to an <a href='" . hoturl("assign") . "'>assignment page</a>
and clicks on “Request review” without entering a name
or email address. This reports the token, a short string of letters and
numbers such as “9HDZYUB”. Any user who knows the token can
enter it on HotCRP’s home page, after which the system lets them
view the paper and anonymously modify the corresponding “Jane Q. Public”
review. True reviewer identities will not appear in HotCRP’s
database or its logs.
For even more privacy, the paper administrator could collect
offline review forms via email and upload them using
review tokens; then even web server access logs store only the
administrator’s identity.</p>";


    echo $hth->subhead("Before the meeting");
    echo "
<ol>

<li><p><strong><a href='" . hoturl("settings", "group=dec") . "'>Collect
  authors’ responses to the reviews (optional).</a></strong>  Authors’ responses
  (also called rebuttals) let authors correct reviewer misconceptions
  before decisions are made.  Responses are entered
  into the system as comments.  On the <a
  href='" . hoturl("settings", "group=dec") . "'>decision settings page</a>,
  update “Can authors see reviews” and “Collect responses to the
  reviews,” then <a href='" . hoturl("mail") . "'>send mail to
  authors</a> informing them of the response deadline.  PC members can still
  update their reviews up to the <a
  href='" . hoturl("settings", "group=reviews") . "'>review deadline</a>; authors
  are informed via email of any review changes.  At the end of the response
  period you should generally <a
  href='" . hoturl("settings", "group=dec") . "'>turn off “Authors can see
  reviews”</a> so PC members can update their reviews in peace.</p></li>

<li><p>Set <strong><a href='" . hoturl("settings", "group=reviews") . "'>PC can
  see all reviews</a></strong> if you haven’t already, allowing the program
  committee to see reviews and scores for
  non-conflicted papers.  (During most conferences’ review periods, a PC member
  can see a paper’s reviews only after completing their own
  review for that paper.  This supposedly reduces bias.)</p></li>

<li><p><strong><a href='" . hoturl("search", "q=&amp;t=s&amp;sort=50") . "'>Examine
  paper scores</a></strong>, either one at a time or en masse, and decide
  which papers will be discussed.  The <a
  href='" . hoturl("help", "t=tags") . "'>tags</a> system lets you prepare
  discussion sets.  Use <a href='" . hoturl("help", "t=keywords") . "'>search
  keywords</a> to, for example, find all papers with at least two overall
  merit ratings of 2 or better.</p></li>

<li><p><strong>Assign discussion orders using <a
  href='" . hoturl("help", "t=tags#values") . "'>tags</a></strong> (optional).  Common
  discussion orders include sorted by overall ranking (high-to-low,
  low-to-high, or alternating), sorted by topic, and <a href=\"" .
  hoturl("autoassign", "a=discorder") . "\">grouped by PC conflicts</a>.
  Explicit tag-based orders make it easier for the PC to follow along.</p></li>

<li><p><strong><a href='" . hoturl("autoassign") . "'>Assign discussion leads
  (optional).</a></strong> Discussion leads are expected to be able to
  summarize the paper and the reviews.  You can assign leads either <a
  href='" . hoturl("assign") . "'>paper by paper</a> or <a
  href='" . hoturl("autoassign") . "'>automatically</a>.</p></li>

<li><p><strong><a href='" . hoturl("settings", "group=dec") . "'>Define decision
  types (optional).</a></strong> By default, HotCRP has two decision types,
  “accept” and “reject,” but you can add other types of acceptance and
  rejection, such as “accept as short paper.”</p></li>

<li><p>The night before the meeting, <strong><a
  href='" . hoturl("search", "q=&amp;t=s") . "'>download all
  reviews onto a laptop</a></strong> (Download &gt; All reviews) in case the
  Internet explodes and you can’t reach HotCRP from the meeting
  place.</p></li>

</ol>";


    echo $hth->subhead("At the meeting", "meeting");
    echo "
<ol>

<li><p>The <b>meeting tracker</b> can keep the PC coordinated.
  Search for papers in whatever order you like (you may want an explicit
  <a href=\"" . hoturl("help", "t=tags#values") . "\">discussion order</a>).
  Then open a browser tab to manage the tracker, navigate to the first paper in
  the order, and select “&#9759;” to activate the tracker.
  From that point on, PC members see a banner with the tracker
  tab’s current position in the order:</p>
  " . Ht::img("extracker.png", "[Meeting tracker]", ["style" => "max-width:714px"]) . "
  <p>You can also view the discussion
  status on the <a href=\"" . hoturl("buzzer") . "\">discussion
  status page</a>.</p></li>

<li><p>Scribes can capture discussions as comments for the authors’
  reference.</p></li>

<li><p><strong>Paper decisions</strong> can be recorded on the <a
  href='" . hoturl("review") . "'>paper pages</a> or en masse via <a
  href='" . hoturl("search", "q=&amp;t=s") . "'>search</a>.  Use <a
  href='" . hoturl("settings", "group=dec") . "'>decision settings</a> to expose
  decisions to PC members if desired.</p></li>

<li><p><strong>Shepherding (optional).</strong> If your conference uses
  shepherding for accepted papers, you can assign shepherds either <a
  href='" . hoturl("paper") . "'>paper by paper</a> or <a
  href='" . hoturl("autoassign", "t=acc") . "'>automatically</a>.</p></li>

</ol>";

    if (!$user->conf->setting("shepherd_hide"))
        $shepherd_visible = " This will also make shepherd names visible to authors.";
    else
        $shepherd_visible = "";

    echo $hth->subhead("After the meeting");
    echo "
<ol>

<li><p><strong><a
  href='" . hoturl("search", "q=&amp;t=s") . "'>Enter
  decisions</a> and <a
  href='" . hoturl("search", "q=dec:yes&amp;t=s") . "'>shepherds</a></strong>
  if you didn’t do this at the meeting.</p></li>

<li><p>Give reviewers some time to <strong>update their reviews</strong> in
  response to PC discussion (optional).</p></li>

<li><p>Set <a href='" . hoturl("settings", "group=dec") . "'>“Who can
  <strong>see decisions?</strong>”</a> to “Authors, PC members,
  and reviewers.”$shepherd_visible</p></li>

<li><p><strong><a href='" . hoturl("mail") . "'>Send mail to
  authors</a></strong> informing them that reviews and decisions are
  available.  The mail can also contain the reviews and comments
  themselves.</p></li>

<li><p><strong><a href='" . hoturl("settings", "group=dec") . "'>Collect final
  papers (optional).</a></strong> If you’re putting together the program
  yourself, it can be convenient to collect final versions using HotCRP.
  Authors upload final versions just as they did submissions.  You can then <a
  href='" . hoturl("search", "q=dec:yes&amp;t=s") . "'>download
  all final versions as a <code>.zip</code> archive</a>.  (The submitted
  versions are archived for reference.)</p></li>

</ol>";
}



echo '<div class="leftmenu_menucontainer"><div class="leftmenu_list">';
foreach ($help_topics->groups() as $gj) {
    if ($gj->name === $topic)
        echo '<div class="leftmenu_item_on">', $gj->title, '</div>';
    else if (isset($gj->title))
        echo '<div class="leftmenu_item">',
            '<a href="', hoturl("help", "t=$gj->name"), '">', $gj->title, '</a></div>';
    if ($gj->name === "topics")
        echo '<div class="c g"></div>';
}
echo "</div></div>\n",
    '<div class="leftmenu_content_container"><div class="leftmenu_content">',
    '<div id="helpcontent" class="leftmenu_body">';
Ht::stash_script("jQuery(\".leftmenu_item\").click(divclick)");

echo '<h2 class="helppage">', $topicj->title, '</h2>';
$hth->echo_topic($topic);
echo "</div></div></div>\n";


$Conf->footer();
