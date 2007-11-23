<?php

function createProject($name)
{
    $checkquery = mysql_query("SELECT id FROM projects WHERE title='" . mysql_real_escape_string($name) . "'");
    if (mysql_fetch_row($checkquery)) return;

    mysql_query("INSERT INTO projects(title) VALUES ('" . mysql_real_escape_string($name) . "')");
}

function addUserToProject($projectname, $username)
{
    mysql_query("INSERT INTO usersprojects(user, project, access) VALUES ((SELECT u.id FROM users u WHERE u.username='" . mysql_real_escape_string($username) . "'), (SELECT p.id FROM projects p WHERE p.title='" . mysql_real_escape_string($projectname) . "'), 0xFFFFFF)");
}
