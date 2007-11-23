<?php

require_once('./include/Projects.php');

function globalAdministration()
{
    global $sepsadminCreateProjects;
    if ($sepsadminCreateProjects)
    {
        foreach($sepsadminCreateProjects as $project)
        {
            createProject($project[0]);
            echo '<div class="infomsg">Vytvořen projekt ' . htmlspecialchars($project[0]) . '</div>';
            addUserToProject($project[0], $project[1]);
            echo '<div class="infomsg">Do projektu ' . htmlspecialchars($project[0]) . ' přidán uživatel ' . htmlspecialchars($project[1]) . '</div>';
        }
    }
}