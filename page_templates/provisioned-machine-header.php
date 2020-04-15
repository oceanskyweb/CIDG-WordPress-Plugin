<h2><strong>License Key:</strong> [license_key]</h2>
---<strong>Machines Activated:</strong> [machines_activated_count] out of [license_count]<br/>
---<strong>Activate you license:</strong> [activation_url]

<?php
// Hide if there are no active machines.
if (!empty($machines)) : ?>
    <table class="shop_table shop_table_responsive">
        <tr>
            <th>Machine ID</th>
            <th>Activation Date</th>
            <th>Action</th>
        </tr>
<?php endif; ?>
