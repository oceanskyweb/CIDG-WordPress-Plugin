<?php
// Hide if there are no active machines.
if (!empty($machines)) : ?>
    <tr>
        <td>[machine_id]</td>
        <td>[activation_date]</td>
        <td>
            <form method="post">
                <input type="submit" name="<?php echo PLUGIN_PREFIX; ?>_action" value="Deactivate Machine" />
                <input type="hidden" name="license_key" value="[license_key]" />
                <input type="hidden" name="machine_id" value="[machine_id]" />
                <input type="hidden" name="order_id" value="[order_id]" />
            </form>
        </td>
    </tr>
<?php endif; ?>
