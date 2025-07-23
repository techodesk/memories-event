<?php
/**
 * Helper functions for guest forms.
 */

/**
 * Render a multi-select input using Choices.js compatible markup.
 *
 * @param array<int, array{ id:int, name:string, email:string }> $guests
 * @param array<int|string> $selectedIds
 * @param string $name
 * @param string $id
 * @return string
 */
function renderGuestSelectInput(array $guests, array $selectedIds = [], string $name = 'guest_ids[]', string $id = 'guestSelect'): string
{
    $options = '';
    foreach ($guests as $g) {
        $sel = in_array($g['id'], $selectedIds, true) ? ' selected' : '';
        $label = htmlspecialchars(trim(($g['name'] ?? '') . ' (' . ($g['email'] ?? '') . ')'));
        $options .= "<option value=\"{$g['id']}\"$sel>$label</option>\n";
    }
    return "<select class=\"form-select\" name=\"$name\" id=\"$id\" multiple>\n$options</select>";
}
