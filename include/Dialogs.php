<?php

function beginDialog($caption)
{
    echo '<div class="modal" role="dialog" aria-labelledby="modal-title"><div class="modal-dialog"><div class="modal-content">';
    echo '<div class="modal-header"><button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Zavřít</span></button><h4 class="modal-title" id="modal-title">';
    echo $caption;
    echo '</h4></div>';
}

function beginDialogBody()
{
    echo '<div class="modal-body">';
}

function endDialogBody()
{
    echo '</div>';
}

function beginDialogFooter()
{
    echo '<div class="modal-footer">';
}

function endDialogFooter()
{
    echo '</div>';
}

function endDialog()
{
	echo '</div></div></div>';
}

function alert($text, $type = 'default')
{
    echo "<div class='alert alert-$type' role='alert'>$text</div>";
}

function beginPanel($caption = null, $type = 'default')
{
	echo "<div class='panel panel-$type'>";
    if ($caption) echo "<div class='panel-heading'>$caption</div>";
}

function beginPanelBody()
{
    echo '<div class="panel-body">';
}

function endPanelBody()
{
    echo '</div>';
}

function beginPanelFooter()
{
    echo '<div class="panel-footer">';
}

function endPanelFooter()
{
    echo '</div>';
}

function endPanel()
{
	echo '</div>';
}
