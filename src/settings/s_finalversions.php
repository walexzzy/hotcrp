<?php
// src/settings/s_finalversions.php -- HotCRP settings > final versions page
// Copyright (c) 2006-2018 Eddie Kohler; see LICENSE.

class FinalVersions_SettingParser extends SettingParser {
    static function render(SettingValues $sv) {
        $sv->echo_messages_near("final_open");
        echo '<div class="has-fold fold2o">';
        $sv->echo_checkbox('final_open', '<strong>Collect final versions of accepted papers</strong>', ["class" => "js-foldup", "item_class" => "settings-g"]);
        echo '<div class="fx2"><div class="settings-g">';
        $sv->echo_entry_group("final_soft", "Deadline", ["horizontal" => true]);
        $sv->echo_entry_group("final_done", "Hard deadline", ["horizontal" => true]);
        $sv->echo_entry_group("final_grace", "Grace period", ["horizontal" => true]);
        echo '</div><div class="settings-g">';
        $sv->echo_message_minor("msg.finalsubmit", "Instructions");
        echo '</div>';
        BanalSettings::render("_m1", $sv);
        echo "</div>",
            "<p class=\"settingtext\">To collect <em>multiple</em> final versions, such as one in 9pt and one in 11pt, add “Alternate final version” options via <a href='", hoturl("settings", "group=opt"), "'>Settings &gt; Submission options</a>.</p>",
            "</div>\n\n";
        Ht::stash_script("foldup.call(\$\$('cbfinal_open'), null)");
    }

    static function crosscheck(SettingValues $sv) {
        global $Now;
        if ($sv->has_interest("final_open")
            && $sv->newv("final_open")
            && ($sv->newv("final_soft") || $sv->newv("final_done"))
            && (!$sv->newv("final_done") || $sv->newv("final_done") > $Now)
            && $sv->newv("seedec") != Conf::SEEDEC_ALL)
            $sv->warning_at(null, "The system is set to collect final versions, but authors cannot submit final versions until they know their papers have been accepted. You may want to update the the “Who can see paper decisions” setting.");
    }
}
